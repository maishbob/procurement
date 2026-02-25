<?php

namespace App\Modules\PurchaseOrders\Services;

use App\Modules\PurchaseOrders\Models\PurchaseOrder;
use App\Modules\PurchaseOrders\Models\PurchaseOrderItem;
use App\Modules\Requisitions\Models\Requisition;
use App\Core\Audit\AuditService;
use App\Core\Workflow\WorkflowEngine;
use App\Core\Rules\GovernanceRules;
use App\Core\CurrencyEngine\CurrencyEngine;
use App\Core\TaxEngine\TaxEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Carbon\Carbon;

/**
 * Purchase Order Service Layer
 * 
 * Business logic for purchase order management
 */
class PurchaseOrderService
{
    protected AuditService $auditService;
    protected WorkflowEngine $workflowEngine;
    protected GovernanceRules $governanceRules;
    protected CurrencyEngine $currencyEngine;
    protected TaxEngine $taxEngine;

    public function __construct(
        AuditService $auditService,
        WorkflowEngine $workflowEngine,
        GovernanceRules $governanceRules,
        CurrencyEngine $currencyEngine,
        TaxEngine $taxEngine
    ) {
        $this->auditService = $auditService;
        $this->workflowEngine = $workflowEngine;
        $this->governanceRules = $governanceRules;
        $this->currencyEngine = $currencyEngine;
        $this->taxEngine = $taxEngine;
    }

    /**
     * Create a new purchase order from requisition
     */
    public function createFromRequisition(Requisition $requisition, int $supplierId, array $data): PurchaseOrder
    {
        if (!$requisition->isApproved()) {
            throw new Exception("Can only create PO from approved requisition");
        }

        // Enforce segregation of duties
        $this->governanceRules->enforceSegregationOfDuties(
            Auth::id(),
            'create_po',
            $requisition,
            ['created', 'approved']
        );

        // Enforce Framework Contract validity limits
        if (!empty($data['procurement_process_id'])) {
            $process = \App\Models\ProcurementProcess::find($data['procurement_process_id']);
            if ($process && $process->type === 'framework_agreement') {
                $bid = \App\Models\SupplierBid::where('procurement_process_id', $process->id)
                    ->where('supplier_id', $supplierId)
                    ->where('status', 'awarded')
                    ->first();
                
                if ($bid && $bid->validity_days && $process->awarded_at) {
                    // Check if current date is past the validity period
                    $expiryDate = $process->awarded_at->copy()->addDays($bid->validity_days);
                    if (now()->greaterThan($expiryDate)) {
                        throw new Exception("Cannot create PO. The framework agreement expired on {$expiryDate->format('Y-m-d')}.");
                    }
                }
            }
        }

        return DB::transaction(function () use ($requisition, $supplierId, $data) {
            // Generate PO number
            $data['po_number'] = $this->generatePONumber();
            $data['requisition_id'] = $requisition->id;
            $data['supplier_id'] = $supplierId;
            $data['department_id'] = $requisition->department_id;
            $data['ordered_by'] = Auth::id();
            $data['status'] = 'draft';
            $data['po_date'] = Carbon::today();
            $data['currency'] = $data['currency'] ?? 'KES';

            // Lock exchange rate if not KES
            if ($data['currency'] !== 'KES') {
                $rate = $this->currencyEngine->lockRate($data['currency'], 'KES');
                $data['exchange_rate'] = $rate;
            }

            // Create PO
            $po = PurchaseOrder::create($data);

            // Create line items from requisition
            $this->createLineItemsFromRequisition($po, $requisition, $data['items'] ?? []);

            // Calculate totals
            $this->calculateTotals($po);

            // Audit log
            $this->auditService->logCreate(
                PurchaseOrder::class,
                $po->id,
                $po->toArray(),
                ['module' => 'purchase_orders', 'requisition_id' => $requisition->id]
            );

            return $po->load('items');
        });
    }

    /**
     * Submit PO for approval
     */
    public function submitForApproval(PurchaseOrder $po): PurchaseOrder
    {
        if (!$po->isDraft()) {
            throw new Exception("Only draft PO can be submitted");
        }

        return DB::transaction(function () use ($po) {
            $this->workflowEngine->transition(
                $po,
                'purchase_order',
                'draft',
                'pending_approval',
                'Submitted for approval'
            );

            return $po->fresh();
        });
    }

    /**
     * Approve purchase order
     */
    public function approve(PurchaseOrder $po, ?string $comments = null): PurchaseOrder
    {
        if (!$po->canBeApproved()) {
            throw new Exception("PO cannot be approved in {$po->status} state");
        }

        return DB::transaction(function () use ($po, $comments) {
            $approverId = Auth::id();

            // Enforce segregation of duties
            $this->governanceRules->enforceSegregationOfDuties(
                $approverId,
                'approve',
                $po,
                ['created']
            );

            $this->workflowEngine->transition(
                $po,
                'purchase_order',
                $po->status,
                'approved',
                $comments ?? 'Approved'
            );

            $po->approved_by = $approverId;
            $po->approved_at = Carbon::now();
            $po->save();

            $this->auditService->logApproval(
                PurchaseOrder::class,
                $po->id,
                'approved',
                'approver',
                $comments,
                ['approver_id' => $approverId]
            );

            return $po->fresh();
        });
    }

    /**
     * Issue PO to supplier
     */
    public function issue(PurchaseOrder $po): PurchaseOrder
    {
        if (!$po->canBeIssued()) {
            throw new Exception("PO cannot be issued in {$po->status} state");
        }

        return DB::transaction(function () use ($po) {
            $this->workflowEngine->transition(
                $po,
                'purchase_order',
                'approved',
                'issued',
                'Issued to supplier'
            );

            $po->issued_at = Carbon::now();
            $po->save();

            // Send notification to supplier
            $this->notifySupplier($po);

            return $po->fresh();
        });
    }

    /**
     * Cancel purchase order
     */
    public function cancel(PurchaseOrder $po, string $reason): PurchaseOrder
    {
        if (!$po->canBeCancelled()) {
            throw new Exception("PO cannot be cancelled in {$po->status} state");
        }

        return DB::transaction(function () use ($po, $reason) {
            $this->workflowEngine->transition(
                $po,
                'purchase_order',
                $po->status,
                'cancelled',
                $reason
            );

            $po->cancellation_reason = $reason;
            $po->cancelled_at = Carbon::now();
            $po->save();

            // Release budget commitments
            $this->releaseBudgetCommitments($po);

            return $po->fresh();
        });
    }

    /**
     * Generate unique PO number
     */
    protected function generatePONumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "PO-{$year}{$month}";

        $lastPO = PurchaseOrder::where('po_number', 'LIKE', "{$prefix}%")
            ->orderBy('po_number', 'desc')
            ->first();

        if ($lastPO) {
            $lastNumber = (int) substr($lastPO->po_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create line items from requisition
     */
    protected function createLineItemsFromRequisition(PurchaseOrder $po, Requisition $requisition, array $itemPricing): void
    {
        $lineNumber = 1;
        foreach ($requisition->items as $reqItem) {
            $pricing = $itemPricing[$reqItem->id] ?? [];

            $unitPrice = $pricing['unit_price'] ?? $reqItem->estimated_unit_price;
            $quantity = $reqItem->quantity;
            $lineSubtotal = $unitPrice * $quantity;

            // Calculate VAT
            $taxCalc = $this->taxEngine->calculateVAT(
                $lineSubtotal,
                $reqItem->vat_type ?? 'vatable'
            );

            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'requisition_item_id' => $reqItem->id,
                'line_number' => $lineNumber++,
                'description' => $reqItem->description,
                'specifications' => $reqItem->specifications,
                'quantity' => $quantity,
                'unit_of_measure' => $reqItem->unit_of_measure,
                'unit_price' => $unitPrice,
                'line_subtotal' => $lineSubtotal,
                'vat_rate' => $taxCalc['rate'],
                'vat_amount' => $taxCalc['vat_amount'],
                'line_total' => $taxCalc['total_with_vat'],
                'is_vatable' => $reqItem->is_vatable,
                'vat_type' => $reqItem->vat_type,
                'subject_to_wht' => $reqItem->subject_to_wht,
                'wht_type' => $reqItem->wht_type,
                'quantity_received' => 0,
                'quantity_outstanding' => $quantity,
                'receiving_status' => 'pending',
            ]);
        }
    }

    /**
     * Calculate PO totals
     */
    protected function calculateTotals(PurchaseOrder $po): void
    {
        $items = $po->items;

        $subtotal = $items->sum('line_subtotal');
        $vatAmount = $items->sum('vat_amount');
        $total = $items->sum('line_total');

        // Convert to base currency if needed
        if ($po->currency !== 'KES') {
            $totalBase = $this->currencyEngine->toBase($total, $po->currency);
        } else {
            $totalBase = $total;
        }

        $po->update([
            'subtotal' => $subtotal,
            'vat_amount' => $vatAmount,
            'total_amount' => $total,
            'total_amount_base' => $totalBase,
        ]);
    }

    /**
     * Notify supplier (queued)
     */
    protected function notifySupplier(PurchaseOrder $po): void
    {
        // Queue notification job
    }

    /**
     * Release budget commitments
     */
    protected function releaseBudgetCommitments(PurchaseOrder $po): void
    {
        // Implementation would release budget commitments
    }
}
