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
     * Update budget spent amounts
     */
    protected function updateBudgetSpent(Payment $payment): void
    {
        // Implementation would update budget line spent amounts
        // based on the invoices being paid
    }
}
