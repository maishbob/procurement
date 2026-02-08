<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\SupplierInvoice;
use App\Modules\Finance\Models\SupplierInvoiceItem;
use App\Modules\PurchaseOrders\Models\PurchaseOrder;
use App\Modules\GRN\Models\GoodsReceivedNote;
use App\Core\Audit\AuditService;
use App\Core\Workflow\WorkflowEngine;
use App\Core\Rules\GovernanceRules;
use App\Core\TaxEngine\TaxEngine;
use App\Core\CurrencyEngine\CurrencyEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Carbon\Carbon;

/**
 * Supplier Invoice Service Layer
 * 
 * Handles invoice management with three-way matching and eTIMS integration
 */
class InvoiceService
{
    protected AuditService $auditService;
    protected WorkflowEngine $workflowEngine;
    protected GovernanceRules $governanceRules;
    protected TaxEngine $taxEngine;
    protected CurrencyEngine $currencyEngine;

    public function __construct(
        AuditService $auditService,
        WorkflowEngine $workflowEngine,
        GovernanceRules $governanceRules,
        TaxEngine $taxEngine,
        CurrencyEngine $currencyEngine
    ) {
        $this->auditService = $auditService;
        $this->workflowEngine = $workflowEngine;
        $this->governanceRules = $governanceRules;
        $this->taxEngine = $taxEngine;
        $this->currencyEngine = $currencyEngine;
    }

    /**
     * Create invoice from GRN
     */
    public function createFromGRN(GoodsReceivedNote $grn, array $data): SupplierInvoice
    {
        if (!$grn->isApproved()) {
            throw new Exception("Can only create invoice from approved GRN");
        }

        return DB::transaction(function () use ($grn, $data) {
            // Generate invoice number
            $data['invoice_number'] = $this->generateInvoiceNumber();
            $data['purchase_order_id'] = $grn->purchase_order_id;
            $data['grn_id'] = $grn->id;
            $data['supplier_id'] = $grn->supplier_id;
            $data['status'] = 'draft';
            $data['currency'] = $grn->purchaseOrder->currency;
            $data['exchange_rate'] = $grn->purchaseOrder->exchange_rate;
            $data['three_way_match_status'] = 'pending';

            // Create invoice
            $invoice = SupplierInvoice::create($data);

            // Create line items
            $this->createLineItemsFromGRN($invoice, $grn, $data['items'] ?? []);

            // Calculate totals with tax
            $this->calculateTotals($invoice);

            // Audit log
            $this->auditService->logCreate(
                SupplierInvoice::class,
                $invoice->id,
                $invoice->toArray(),
                ['module' => 'finance', 'grn_id' => $grn->id]
            );

            return $invoice->load('items');
        });
    }

    /**
     * Submit invoice for verification
     */
    public function submitForVerification(SupplierInvoice $invoice): SupplierInvoice
    {
        if (!$invoice->isDraft()) {
            throw new Exception("Only draft invoices can be submitted");
        }

        return DB::transaction(function () use ($invoice) {
            $this->workflowEngine->transition(
                $invoice,
                'invoice',
                'draft',
                'pending_verification',
                'Submitted for verification'
            );

            return $invoice->fresh();
        });
    }

    /**
     * Perform three-way match (PO + GRN + Invoice)
     */
    public function performThreeWayMatch(SupplierInvoice $invoice): array
    {
        $po = $invoice->purchaseOrder;
        $grn = $invoice->grn;

        if (!$po || !$grn) {
            throw new Exception("Three-way match requires linked PO and GRN");
        }

        $matchResults = [
            'passed' => false,
            'details' => [],
            'variances' => [],
        ];

        // Compare PO vs GRN vs Invoice amounts
        $tolerance = config('procurement.three_way_match.tolerance_percentage', 2);

        // Check total amounts
        $poTotal = $po->total_amount;
        $grnTotal = $grn->total_quantity_accepted * $po->items->avg('unit_price'); // Simplified
        $invoiceTotal = $invoice->total_amount;

        // Calculate variance
        $poInvoiceVariance = $this->governanceRules->validateThreeWayMatch(
            $poTotal,
            $invoiceTotal,
            $tolerance
        );

        if ($poInvoiceVariance['passed']) {
            $matchResults['passed'] = true;
            $matchResults['details']['po_invoice_match'] = 'Passed';
        } else {
            $matchResults['details']['po_invoice_match'] = 'Failed';
            $matchResults['variances'][] = [
                'type' => 'po_invoice',
                'po_amount' => $poTotal,
                'invoice_amount' => $invoiceTotal,
                'variance_percentage' => $poInvoiceVariance['variance_percentage'],
                'tolerance' => $tolerance,
            ];
        }

        // Check line item quantities
        foreach ($invoice->items as $invItem) {
            $poItem = $po->items->where('id', $invItem->purchase_order_item_id)->first();
            $grnItem = $grn->items->where('purchase_order_item_id', $invItem->purchase_order_item_id)->first();

            if (!$poItem || !$grnItem) {
                $matchResults['passed'] = false;
                $matchResults['variances'][] = [
                    'type' => 'missing_reference',
                    'line' => $invItem->line_number,
                    'description' => $invItem->description,
                ];
                continue;
            }

            // Quantity check
            if ($invItem->quantity != $grnItem->quantity_accepted) {
                $matchResults['passed'] = false;
                $matchResults['variances'][] = [
                    'type' => 'quantity_mismatch',
                    'line' => $invItem->line_number,
                    'grn_quantity' => $grnItem->quantity_accepted,
                    'invoice_quantity' => $invItem->quantity,
                ];
            }

            // Price check
            $priceVariance = abs($invItem->unit_price - $poItem->unit_price);
            $priceVariancePct = ($priceVariance / $poItem->unit_price) * 100;

            if ($priceVariancePct > $tolerance) {
                $matchResults['passed'] = false;
                $matchResults['variances'][] = [
                    'type' => 'price_variance',
                    'line' => $invItem->line_number,
                    'po_price' => $poItem->unit_price,
                    'invoice_price' => $invItem->unit_price,
                    'variance_percentage' => $priceVariancePct,
                ];
            }
        }

        // Update invoice with match results
        $invoice->update([
            'three_way_match_status' => $matchResults['passed'] ? 'passed' : 'failed',
            'three_way_match_passed' => $matchResults['passed'],
            'three_way_match_details' => $matchResults,
            'three_way_match_performed_by' => Auth::id(),
            'three_way_match_performed_at' => Carbon::now(),
        ]);

        // Audit log
        $this->auditService->logCompliance(
            SupplierInvoice::class,
            $invoice->id,
            'three_way_match',
            $matchResults['passed'] ? 'passed' : 'failed',
            $matchResults
        );

        return $matchResults;
    }

    /**
     * Verify invoice (accountant review)
     */
    public function verify(SupplierInvoice $invoice, ?string $comments = null): SupplierInvoice
    {
        if (!$invoice->canBeVerified()) {
            throw new Exception("Invoice cannot be verified in {$invoice->status} state");
        }

        return DB::transaction(function () use ($invoice, $comments) {
            $verifierId = Auth::id();

            // Perform three-way match if not done
            if ($invoice->three_way_match_status === 'pending') {
                $this->performThreeWayMatch($invoice);
                $invoice->fresh();
            }

            // Can only verify if three-way match passed
            if (!$invoice->three_way_match_passed) {
                throw new Exception("Invoice failed three-way match and cannot be verified");
            }

            $this->workflowEngine->transition(
                $invoice,
                'invoice',
                $invoice->status,
                'verified',
                $comments ?? 'Verified'
            );

            $invoice->verified_by = $verifierId;
            $invoice->verified_at = Carbon::now();
            $invoice->save();

            $this->auditService->logApproval(
                SupplierInvoice::class,
                $invoice->id,
                'verified',
                'verifier',
                $comments,
                ['verifier_id' => $verifierId]
            );

            return $invoice->fresh();
        });
    }

    /**
     * Approve invoice for payment
     */
    public function approve(SupplierInvoice $invoice, ?string $comments = null): SupplierInvoice
    {
        if (!$invoice->canBeApproved()) {
            throw new Exception("Invoice cannot be approved in {$invoice->status} state");
        }

        return DB::transaction(function () use ($invoice, $comments) {
            $approverId = Auth::id();

            // Enforce segregation of duties
            $this->governanceRules->enforceSegregationOfDuties(
                $approverId,
                'approve',
                $invoice,
                ['verified']
            );

            $this->workflowEngine->transition(
                $invoice,
                'invoice',
                'verified',
                'approved_for_payment',
                $comments ?? 'Approved for payment'
            );

            $invoice->approved_by = $approverId;
            $invoice->approved_at = Carbon::now();
            $invoice->save();

            $this->auditService->logApproval(
                SupplierInvoice::class,
                $invoice->id,
                'approved',
                'approver',
                $comments,
                ['approver_id' => $approverId]
            );

            return $invoice->fresh();
        });
    }

    /**
     * Verify eTIMS invoice
     */
    public function verifyETIMS(SupplierInvoice $invoice): bool
    {
        // This would integrate with KRA eTIMS API
        // For now, just log the check
        $this->auditService->logCompliance(
            SupplierInvoice::class,
            $invoice->id,
            'etims_verification',
            'checked',
            [
                'control_number' => $invoice->etims_control_number,
                'reference' => $invoice->etims_invoice_reference,
            ]
        );

        return true;
    }

    /**
     * Generate unique invoice number
     */
    protected function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "INV-{$year}{$month}";

        $lastInv = SupplierInvoice::where('invoice_number', 'LIKE', "{$prefix}%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInv) {
            $lastNumber = (int) substr($lastInv->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create line items from GRN
     */
    protected function createLineItemsFromGRN(SupplierInvoice $invoice, GoodsReceivedNote $grn, array $itemData): void
    {
        $lineNumber = 1;
        foreach ($grn->items as $grnItem) {
            $data = $itemData[$grnItem->id] ?? [];

            $unitPrice = $data['unit_price'] ?? $grnItem->purchaseOrderItem->unit_price;
            $quantity = $grnItem->quantity_accepted;
            $lineSubtotal = $unitPrice * $quantity;

            // Calculate VAT
            $poItem = $grnItem->purchaseOrderItem;
            if ($poItem->is_vatable) {
                $taxCalc = $this->taxEngine->calculateVAT($lineSubtotal, $poItem->vat_type);
            } else {
                $taxCalc = [
                    'rate' => 0,
                    'vat_amount' => 0,
                    'total_with_vat' => $lineSubtotal,
                ];
            }

            SupplierInvoiceItem::create([
                'supplier_invoice_id' => $invoice->id,
                'purchase_order_item_id' => $poItem->id,
                'grn_item_id' => $grnItem->id,
                'line_number' => $lineNumber++,
                'description' => $poItem->description,
                'quantity' => $quantity,
                'unit_of_measure' => $poItem->unit_of_measure,
                'unit_price' => $unitPrice,
                'line_subtotal' => $lineSubtotal,
                'vat_rate' => $taxCalc['rate'],
                'vat_amount' => $taxCalc['vat_amount'],
                'line_total' => $taxCalc['total_with_vat'],
            ]);
        }
    }

    /**
     * Calculate invoice totals
     */
    protected function calculateTotals(SupplierInvoice $invoice): void
    {
        $items = $invoice->items;

        $subtotal = $items->sum('line_subtotal');
        $vatAmount = $items->sum('vat_amount');
        $total = $items->sum('line_total');

        // Calculate WHT if applicable
        $supplier = $invoice->supplier;
        $whtAmount = 0;

        if ($supplier->subject_to_wht) {
            $whtCalc = $this->taxEngine->calculateWHT($subtotal, $supplier->wht_type);
            $whtAmount = $whtCalc['wht_amount'];
        }

        // Convert to base currency
        if ($invoice->currency !== 'KES') {
            $totalBase = $this->currencyEngine->toBase($total, $invoice->currency);
        } else {
            $totalBase = $total;
        }

        $amountDue = $total; // Will be reduced as payments are made

        $invoice->update([
            'subtotal' => $subtotal,
            'vat_amount' => $vatAmount,
            'wht_amount' => $whtAmount,
            'total_amount' => $total,
            'amount_due' => $amountDue,
            'amount_paid' => 0,
            'total_amount_base' => $totalBase,
        ]);
    }
}
