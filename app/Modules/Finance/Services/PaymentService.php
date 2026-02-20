<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\SupplierInvoice;
use App\Modules\Finance\Models\WHTCertificate;
use App\Core\Audit\AuditService;
use App\Core\Workflow\WorkflowEngine;
use App\Core\Rules\GovernanceRules;
use App\Core\TaxEngine\TaxEngine;
use App\Core\CurrencyEngine\CurrencyEngine;
use App\Models\BudgetLine;
use App\Services\BudgetService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Carbon\Carbon;

/**
 * Payment Service Layer
 * 
 * Handles payment processing with WHT calculation and certificate generation
 */
class PaymentService
{
    protected AuditService $auditService;
    protected WorkflowEngine $workflowEngine;
    protected GovernanceRules $governanceRules;
    protected TaxEngine $taxEngine;
    protected CurrencyEngine $currencyEngine;
    protected BudgetService $budgetService;

    public function __construct(
        AuditService $auditService,
        WorkflowEngine $workflowEngine,
        GovernanceRules $governanceRules,
        TaxEngine $taxEngine,
        CurrencyEngine $currencyEngine,
        BudgetService $budgetService
    ) {
        $this->auditService = $auditService;
        $this->workflowEngine = $workflowEngine;
        $this->governanceRules = $governanceRules;
        $this->taxEngine = $taxEngine;
        $this->currencyEngine = $currencyEngine;
        $this->budgetService = $budgetService;
    }

    /**
     * Create payment for invoices
     */
    public function create(array $invoiceIds, array $data): Payment
    {
        $invoices = SupplierInvoice::whereIn('id', $invoiceIds)
            ->where('status', 'approved_for_payment')
            ->get();

        if ($invoices->isEmpty()) {
            throw new Exception("No approved invoices found for payment");
        }

        // Verify all invoices are for same supplier
        $supplierIds = $invoices->pluck('supplier_id')->unique();
        if ($supplierIds->count() > 1) {
            throw new Exception("Cannot create payment for multiple suppliers");
        }

        // No-PO-No-Pay / No-GRN-No-Pay / No-Acceptance-No-Pay chain guard
        $this->validatePaymentChain($invoices);

        $supplier = $invoices->first()->supplier;

        return DB::transaction(function () use ($invoices, $supplier, $data) {
            // Generate payment number
            $data['payment_number'] = $this->generatePaymentNumber();
            $data['supplier_id'] = $supplier->id;
            $data['prepared_by'] = Auth::id();
            $data['prepared_at'] = Carbon::now();
            $data['status'] = 'draft';
            $data['currency'] = $invoices->first()->currency;
            $data['exchange_rate'] = $invoices->first()->exchange_rate;

            // Calculate amounts
            $grossAmount = $invoices->sum('amount_due');

            // Calculate WHT
            if ($supplier->subject_to_wht) {
                $whtCalc = $this->taxEngine->calculateWHT(
                    $grossAmount,
                    $supplier->wht_type
                );
                $whtAmount = $whtCalc['wht_amount'];
                $whtRate = $whtCalc['rate'];
            } else {
                $whtAmount = 0;
                $whtRate = 0;
            }

            $netAmount = $grossAmount - $whtAmount;

            // Convert to base currency
            if ($data['currency'] !== 'KES') {
                $grossAmountBase = $this->currencyEngine->toBase($grossAmount, $data['currency']);
                $whtAmountBase = $this->currencyEngine->toBase($whtAmount, $data['currency']);
                $netAmountBase = $this->currencyEngine->toBase($netAmount, $data['currency']);
            } else {
                $grossAmountBase = $grossAmount;
                $whtAmountBase = $whtAmount;
                $netAmountBase = $netAmount;
            }

            // Create payment
            $payment = Payment::create(array_merge($data, [
                'gross_amount' => $grossAmount,
                'wht_amount' => $whtAmount,
                'wht_rate' => $whtRate,
                'net_amount' => $netAmount,
                'gross_amount_base' => $grossAmountBase,
                'wht_amount_base' => $whtAmountBase,
                'net_amount_base' => $netAmountBase,
                'bank_name' => $supplier->bank_name,
                'bank_account_number' => $supplier->bank_account_number,
                'bank_account_name' => $supplier->bank_account_name,
            ]));

            // Link invoices with allocation amounts
            foreach ($invoices as $invoice) {
                $payment->invoices()->attach($invoice->id, [
                    'amount_allocated' => $invoice->amount_due,
                ]);
            }

            // Audit log
            $this->auditService->logCreate(
                Payment::class,
                $payment->id,
                $payment->toArray(),
                [
                    'module' => 'finance',
                    'supplier_id' => $supplier->id,
                    'invoice_count' => $invoices->count(),
                ]
            );

            return $payment->load('invoices');
        });
    }

    /**
     * Submit payment for verification
     */
    public function submitForVerification(Payment $payment): Payment
    {
        if (!$payment->isDraft()) {
            throw new Exception("Only draft payments can be submitted");
        }

        return DB::transaction(function () use ($payment) {
            $this->workflowEngine->transition(
                $payment,
                'payment',
                'draft',
                'pending_verification',
                'Submitted for verification'
            );

            return $payment->fresh();
        });
    }

    /**
     * Verify payment (accountant review)
     */
    public function verify(Payment $payment, ?string $comments = null): Payment
    {
        if (!$payment->canBeVerified()) {
            throw new Exception("Payment cannot be verified in {$payment->status} state");
        }

        return DB::transaction(function () use ($payment, $comments) {
            $verifierId = Auth::id();

            // Enforce segregation of duties
            $this->governanceRules->enforceSegregationOfDuties(
                $verifierId,
                'verify',
                $payment,
                ['created']
            );

            $this->workflowEngine->transition(
                $payment,
                'payment',
                'pending_verification',
                'pending_approval',
                $comments ?? 'Verified'
            );

            $payment->verified_by = $verifierId;
            $payment->verified_at = Carbon::now();
            $payment->save();

            $this->auditService->logApproval(
                Payment::class,
                $payment->id,
                'verified',
                'verifier',
                $comments,
                ['verifier_id' => $verifierId]
            );

            return $payment->fresh();
        });
    }

    /**
     * Approve payment (finance manager/principal)
     */
    public function approve(Payment $payment, ?string $comments = null): Payment
    {
        if (!$payment->canBeApproved()) {
            throw new Exception("Payment cannot be approved in {$payment->status} state");
        }

        return DB::transaction(function () use ($payment, $comments) {
            $approverId = Auth::id();

            // Validate eTIMS compliance for all invoices
            $this->validateEtimsCompliance($payment);

            // Enforce segregation of duties
            $this->governanceRules->enforceSegregationOfDuties(
                $approverId,
                'approve',
                $payment,
                ['created', 'verified']
            );

            $this->workflowEngine->transition(
                $payment,
                'payment',
                'pending_approval',
                'approved',
                $comments ?? 'Approved'
            );

            $payment->approved_by = $approverId;
            $payment->approved_at = Carbon::now();
            $payment->save();

            $this->auditService->logApproval(
                Payment::class,
                $payment->id,
                'approved',
                'approver',
                $comments,
                ['approver_id' => $approverId]
            );

            return $payment->fresh();
        });
    }

    /**
     * Process payment (execute bank transfer/cheque)
     */
    public function process(Payment $payment, array $data): Payment
    {
        if (!$payment->canBeProcessed()) {
            throw new Exception("Payment cannot be processed in {$payment->status} state");
        }

        return DB::transaction(function () use ($payment, $data) {
            $processedBy = Auth::id();

            // Enforce segregation of duties
            $this->governanceRules->enforceSegregationOfDuties(
                $processedBy,
                'process',
                $payment,
                ['created', 'verified', 'approved']
            );

            $this->workflowEngine->transition(
                $payment,
                'payment',
                'approved',
                'processed',
                'Payment processed'
            );

            $payment->update([
                'transaction_id' => $data['transaction_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'cheque_number' => $data['cheque_number'] ?? null,
                'processed_by' => $processedBy,
                'processed_at' => Carbon::now(),
            ]);

            // Update invoices as paid
            foreach ($payment->invoices as $invoice) {
                $allocatedAmount = $invoice->pivot->amount_allocated;

                $invoice->amount_paid += $allocatedAmount;
                $invoice->amount_due -= $allocatedAmount;

                if ($invoice->amount_due <= 0) {
                    $invoice->status = 'paid';
                } else {
                    $invoice->status = 'partially_paid';
                }

                $invoice->save();
            }

            // Generate WHT certificate if applicable
            if ($payment->hasWHT()) {
                $this->generateWHTCertificate($payment);
            }

            // Update budget spent amounts
            $this->updateBudgetSpent($payment);

            $this->auditService->log(
                Payment::class,
                $payment->id,
                'processed',
                'Payment processed and invoices updated',
                ['processed_by' => $processedBy]
            );

            return $payment->fresh();
        });
    }

    /**
     * Generate WHT certificate
     */
    protected function generateWHTCertificate(Payment $payment): WHTCertificate
    {
        $supplier = $payment->supplier;

        $certificate = WHTCertificate::create([
            'certificate_number' => $this->generateWHTCertificateNumber(),
            'payment_id' => $payment->id,
            'supplier_id' => $supplier->id,
            'supplier_kra_pin' => $supplier->kra_pin,
            'financial_year' => $this->getCurrentFinancialYear(),
            'payment_date' => $payment->payment_date,
            'gross_amount' => $payment->gross_amount,
            'wht_rate' => $payment->wht_rate,
            'wht_amount' => $payment->wht_amount,
            'wht_type' => $supplier->wht_type,
            'currency' => $payment->currency,
            'generated_by' => Auth::id(),
            'generated_at' => Carbon::now(),
            'status' => 'active',
        ]);

        $payment->update([
            'wht_certificate_generated' => true,
            'wht_certificate_number' => $certificate->certificate_number,
            'wht_certificate_generated_at' => Carbon::now(),
        ]);

        $this->auditService->logCompliance(
            Payment::class,
            $payment->id,
            'wht_certificate_generated',
            'generated',
            [
                'certificate_number' => $certificate->certificate_number,
                'wht_amount' => $payment->wht_amount,
            ]
        );

        return $certificate;
    }

    /**
     * Validate eTIMS compliance for payment approval
     * 
     * @throws Exception if any invoice lacks eTIMS control number
     */
    protected function validateEtimsCompliance(Payment $payment): void
    {
        $etimsEnabled = config('procurement.etims.enabled', true);
        $etimsEnforcement = config('procurement.etims.enforce_on_payment', true);

        if (!$etimsEnabled || !$etimsEnforcement) {
            return;
        }

        $payment->load('invoices');
        $nonCompliantInvoices = [];

        foreach ($payment->invoices as $invoice) {
            // Must have both a control number AND confirmed eTIMS verification
            if (empty($invoice->etims_control_number) || !$invoice->etims_verified) {
                $nonCompliantInvoices[] = [
                    'invoice_number'          => $invoice->invoice_number,
                    'supplier_invoice_number' => $invoice->supplier_invoice_number,
                    'amount'                  => $invoice->total_amount,
                    'reason'                  => empty($invoice->etims_control_number)
                        ? 'missing control number'
                        : 'eTIMS verification pending',
                ];
            }
        }

        if (!empty($nonCompliantInvoices)) {
            $invoiceList = collect($nonCompliantInvoices)
                ->map(fn($inv) => "{$inv['invoice_number']} ({$inv['reason']})")
                ->join(', ');

            throw new Exception(
                "Payment cannot be approved: eTIMS validation required. " .
                    "The following invoices are not eTIMS-verified: {$invoiceList}. " .
                    "Please ensure all invoices have been verified against KRA eTIMS before approval."
            );
        }

        // Log eTIMS compliance check
        $this->auditService->logCustom(
            Payment::class,
            $payment->id,
            'etims_compliance_validated',
            [
                'invoice_count' => $payment->invoices->count(),
                'validated_at' => Carbon::now(),
                'validated_by' => Auth::id(),
            ]
        );
    }

    /**
     * Generate unique payment number
     */
    protected function generatePaymentNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "PAY-{$year}{$month}";

        $lastPayment = Payment::where('payment_number', 'LIKE', "{$prefix}%")
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate WHT certificate number
     */
    protected function generateWHTCertificateNumber(): string
    {
        $year = date('Y');
        $prefix = "WHT-{$year}";

        $lastCert = WHTCertificate::where('certificate_number', 'LIKE', "{$prefix}%")
            ->orderBy('certificate_number', 'desc')
            ->first();

        if ($lastCert) {
            $lastNumber = (int) substr($lastCert->certificate_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get current financial year
     */
    protected function getCurrentFinancialYear(): string
    {
        // Assuming July-June fiscal year for Kenyan schools
        $now = Carbon::now();
        $fiscalYearStart = Carbon::create($now->year, 7, 1);

        if ($now->lt($fiscalYearStart)) {
            return ($now->year - 1) . '/' . $now->year;
        } else {
            return $now->year . '/' . ($now->year + 1);
        }
    }

    /**
     * Update budget spent amounts when a payment is processed.
     *
     * Traverses: payment → invoices → purchaseOrder → requisition → budget_line_id
     * and calls BudgetService::recordExpenditure() for each linked budget line.
     * Skips invoices that cannot be traced back to a budget line (gracefully).
     */
    public function updateBudgetSpent(Payment $payment): void
    {
        $payment->load('invoices.purchaseOrder.requisition');

        foreach ($payment->invoices as $invoice) {
            $allocatedAmount = (float) $invoice->pivot->amount_allocated;

            $po = $invoice->purchaseOrder;
            if (!$po || !$po->requisition_id) {
                continue;
            }

            $requisition = $po->requisition;
            if (!$requisition || !$requisition->budget_line_id) {
                continue;
            }

            $budgetLine = BudgetLine::find($requisition->budget_line_id);
            if (!$budgetLine) {
                continue;
            }

            $this->budgetService->recordExpenditure(
                $budgetLine,
                $allocatedAmount,
                'Payment',
                $payment->id
            );
        }
    }

    /**
     * No-PO-No-Pay / No-GRN-No-Pay / No-Acceptance-No-Pay chain guard.
     *
     * For each invoice in the proposed payment:
     *   1. Must have a linked, approved Purchase Order.
     *   2. Must have a linked GRN with acceptance_status accepted or partially_accepted.
     *
     * @throws Exception with the invoice number and the broken link.
     */

    protected function validatePaymentChain($invoices): void
    {
        // Ensure $invoices is an Eloquent Collection for load() to work
        if (!$invoices instanceof \Illuminate\Database\Eloquent\Collection) {
            $ids = $invoices->pluck('id');
            $invoices = \App\Modules\Finance\Models\SupplierInvoice::whereIn('id', $ids)
                ->with(['purchaseOrder', 'goodsReceivedNote'])
                ->get();
        } else {
            $invoices->load(['purchaseOrder', 'goodsReceivedNote']);
        }

        $validPoStatuses  = ['approved', 'issued', 'acknowledged', 'fully_received', 'invoiced'];
        $validGrnAcceptance = ['accepted', 'partially_accepted'];

        foreach ($invoices as $invoice) {
            $ref = isset($invoice->invoice_number) ? $invoice->invoice_number : (isset($invoice->id) ? "ID:{$invoice->id}" : 'UNKNOWN');

            // 1. No-PO-No-Pay
            $po = null;
            if (method_exists($invoice, 'getAttribute')) {
                $po = $invoice->getAttribute('purchaseOrder');
            }
            if (!$po && method_exists($invoice, 'getRelationValue')) {
                $po = $invoice->getRelationValue('purchaseOrder');
            }
            if (!$po && isset($invoice->purchaseOrder)) {
                $po = $invoice->purchaseOrder;
            }
            if (!$po && method_exists($invoice, 'purchaseOrder')) {
                $po = $invoice->purchaseOrder;
            }
            if (!$po) {
                throw new Exception(
                    "Payment blocked — invoice {$ref} has no linked Purchase Order (No PO, No Pay)."
                );
            }
            $poStatus = isset($po->status) ? $po->status : null;
            $poNumber = isset($po->po_number) ? $po->po_number : 'UNKNOWN';
            if (!in_array($poStatus, $validPoStatuses)) {
                throw new Exception(
                    "Payment blocked — invoice {$ref}: linked PO #{$poNumber} is in '{$poStatus}' status and has not been approved."
                );
            }

            // 2. No-GRN-No-Pay + No-Acceptance-No-Pay
            $grn = null;
            if (method_exists($invoice, 'getAttribute')) {
                $grn = $invoice->getAttribute('goodsReceivedNote');
            }
            if (!$grn && method_exists($invoice, 'getRelationValue')) {
                $grn = $invoice->getRelationValue('goodsReceivedNote');
            }
            if (!$grn && isset($invoice->goodsReceivedNote)) {
                $grn = $invoice->goodsReceivedNote;
            }
            if (!$grn && method_exists($invoice, 'goodsReceivedNote')) {
                $grn = $invoice->goodsReceivedNote;
            }
            if (!$grn) {
                throw new Exception(
                    "Payment blocked — invoice {$ref} has no linked Goods Received Note (No GRN, No Pay)."
                );
            }
            $acceptanceStatus = isset($grn->acceptance_status) ? $grn->acceptance_status : null;
            $grnNumber = isset($grn->grn_number) ? $grn->grn_number : 'UNKNOWN';
            if (!in_array($acceptanceStatus, $validGrnAcceptance)) {
                throw new Exception(
                    "Payment blocked — invoice {$ref}: GRN #{$grnNumber} has not been accepted by the department (acceptance status: {$acceptanceStatus})."
                );
            }
        }
    }
}
