<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SupplierInvoice;
use App\Services\PaymentService;
use App\Services\ApprovalService;
use App\Core\TaxEngine\TaxEngine;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private ApprovalService $approvalService,
        private TaxEngine $taxEngine
    ) {}

    /**
     * Display all payments with segregation of duties
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        $user = auth()->user();

        $stats = [
            'pending_count' => Payment::where('status', 'pending_approval')->count(),
            'pending_amount' => Payment::where('status', 'pending_approval')->sum('gross_amount'),
            'month_wht' => Payment::whereMonth('created_at', now()->month)
                ->sum('withholding_tax_amount'),
            'month_wht_count' => Payment::whereMonth('created_at', now()->month)
                ->count(),
            'ytd_wht' => Payment::whereYear('created_at', now()->year)
                ->sum('withholding_tax_amount'),
            'ytd_count' => Payment::whereYear('created_at', now()->year)->count(),
        ];

        $filters = [
            'status' => $request->get('status'),
            'supplier_id' => $request->get('supplier_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $payments = $this->paymentService->getAllPayments($filters, 15);

        return view('finance.payments.index', compact('payments', 'stats'));
    }

    /**
     * Show create payment form (Creator role)
     */
    public function create(Request $request)
    {
        $this->authorize('create', Payment::class);

        $invoiceId = $request->get('invoice_id');
        $invoice = $invoiceId ? SupplierInvoice::findOrFail($invoiceId) : null;

        $invoices = SupplierInvoice::where('status', 'verified')
            ->where('payment_status', '!=', 'paid')
            ->get();

        return view('finance.payments.create', compact('invoice', 'invoices'));
    }

    /**
     * Store new payment for approval (Creator role)
     */
    public function store(Request $request)
    {
        $this->authorize('create', Payment::class);

        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:supplier_invoices,id',
            'payment_method' => 'required|in:bank_transfer,cheque,cash,mobile_money',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $payment = $this->paymentService->createPayment(
                $validated,
                auth()->user() // Creator
            );

            return redirect()->route('payments.show', $payment)
                ->with('success', 'Payment created and submitted for approval');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create payment: ' . $e->getMessage());
        }
    }

    /**
     * Display payment details
     */
    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);

        $invoices = $payment->invoices()->get();
        $approvalHistory = $payment->approvals()->with('approver')->get();

        return view('finance.payments.show', compact('payment', 'invoices', 'approvalHistory'));
    }

    /**
     * Show edit form for draft payment (Creator only)
     */
    public function edit(Payment $payment)
    {
        $this->authorize('update', $payment);

        if ($payment->status !== 'draft') {
            return back()->with('error', 'Only draft payments can be edited');
        }

        // Ensure creator can only edit their own
        if ($payment->created_by !== auth()->id()) {
            return back()->with('error', 'You can only edit your own payments');
        }

        return view('finance.payments.edit', compact('payment'));
    }

    /**
     * Update draft payment (Creator only)
     */
    public function update(Request $request, Payment $payment)
    {
        $this->authorize('update', $payment);

        if ($payment->status !== 'draft') {
            return back()->with('error', 'Only draft payments can be updated');
        }

        if ($payment->created_by !== auth()->id()) {
            return back()->with('error', 'You can only edit your own payments');
        }

        $validated = $request->validate([
            'payment_method' => 'sometimes|in:bank_transfer,cheque,cash,mobile_money',
            'payment_date' => 'sometimes|date',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $updated = $this->paymentService->updatePayment($payment, $validated);

            return redirect()->route('payments.show', $updated)
                ->with('success', 'Payment updated');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update payment: ' . $e->getMessage());
        }
    }

    /**
     * Delete draft payment (Creator only)
     */
    public function destroy(Payment $payment)
    {
        $this->authorize('delete', $payment);

        if ($payment->status !== 'draft') {
            return back()->with('error', 'Only draft payments can be deleted');
        }

        if ($payment->created_by !== auth()->id()) {
            return back()->with('error', 'You can only delete your own payments');
        }

        try {
            $payment->delete();

            return redirect()->route('payments.index')
                ->with('success', 'Payment deleted');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete payment: ' . $e->getMessage());
        }
    }

    /**
     * Submit payment for approval (Creator → Approver)
     */
    public function submit(Payment $payment)
    {
        $this->authorize('create', Payment::class);

        if ($payment->status !== 'draft') {
            return back()->with('error', 'Only draft payments can be submitted');
        }

        if ($payment->created_by !== auth()->id()) {
            return back()->with('error', 'You can only submit your own payments');
        }

        try {
            $this->paymentService->submitPayment($payment);

            return back()->with('success', 'Payment submitted for approval');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit payment: ' . $e->getMessage());
        }
    }

    /**
     * Approve payment (Approver role - DIFFERENT from Creator)
     */
    public function approve(Request $request, Payment $payment)
    {
        $this->authorize('approve', $payment);

        $validated = $request->validate([
            'approval_notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->paymentService->approvePayment(
                $payment,
                auth()->user(), // Approver (MUST be different from creator)
                $validated['approval_notes'] ?? ''
            );

            return back()->with('success', 'Payment approved');
        } catch (\Exception $e) {
            return back()->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    /**
     * Reject payment (Approver role - returns to Creator)
     */
    public function reject(Request $request, Payment $payment)
    {
        $this->authorize('approve', $payment);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        try {
            $this->paymentService->rejectPayment(
                $payment,
                auth()->user(),
                $validated['rejection_reason']
            );

            return back()->with('success', 'Payment rejected');
        } catch (\Exception $e) {
            return back()->with('error', 'Rejection failed: ' . $e->getMessage());
        }
    }

    /**
     * Show approval form for approver
     */
    public function approveForm(Payment $payment)
    {
        $this->authorize('approve', $payment);

        if ($payment->status !== 'pending_approval') {
            return back()->with('error', 'Payment is not pending approval');
        }

        $invoices = $payment->invoices()->get();

        return view('finance.payments.approve', compact('payment', 'invoices'));
    }

    /**
     * Process payment (Treasurer role - DIFFERENT from Creator & Approver)
     */
    public function process(Request $request, Payment $payment)
    {
        $this->authorize('process', $payment);

        $validated = $request->validate([
            'reference_number' => 'required|string|unique:payments,reference_number,' . $payment->id,
            'processing_notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->paymentService->processPayment(
                $payment,
                auth()->user(), // Processor/Treasurer (MUST differ from creator & approver)
                $validated['reference_number'],
                $validated['processing_notes'] ?? ''
            );

            return back()->with('success', 'Payment processed successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Download WHT certificate
     */
    public function downloadWHTCertificate(Payment $payment)
    {
        $this->authorize('downloadWHTCert', $payment);

        try {
            if ($payment->withholding_tax_amount <= 0) {
                return back()->with('error', 'No WHT on this payment');
            }

            $pdf = Pdf::loadView('finance.payments.wht-certificate', compact('payment'))
                ->setPaper('a4')
                ->setOption('margin-top', 10);

            return $pdf->download("wht-cert-{$payment->id}.pdf");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate certificate: ' . $e->getMessage());
        }
    }

    /**
     * Display WHT list for period
     */
    public function whtList(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $payments = $this->paymentService->getWHTPayments($filters);

        return view('finance.payments.wht-list', compact('payments'));
    }

    /**
     * Bulk download WHT certificates
     */
    public function bulkDownloadWHT(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        $validated = $request->validate([
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'exists:payments,id',
        ]);

        try {
            // Create ZIP file with multiple PDFs
            $zip = new \ZipArchive();
            $zipPath = storage_path('temp/wht-certs-' . now()->timestamp . '.zip');

            if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
                foreach ($validated['payment_ids'] as $paymentId) {
                    $payment = Payment::findOrFail($paymentId);
                    $pdf = Pdf::loadView('finance.payments.wht-certificate', compact('payment'));
                    $zip->addFromString("wht-cert-{$payment->id}.pdf", $pdf->output());
                }

                $zip->close();

                return response()->download($zipPath, 'wht-certificates.zip');
            }

            return back()->with('error', 'Failed to create ZIP file');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to download: ' . $e->getMessage());
        }
    }

    /**
     * Confirm payment processing (final step)
     */
    public function confirmPayment(Request $request, Payment $payment)
    {
        $this->authorize('process', $payment);

        if ($payment->status !== 'processed') {
            return back()->with('error', 'Payment must be processed first');
        }

        try {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'confirmed_by' => auth()->id(),
            ]);

            return back()->with('success', 'Payment confirmed');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to confirm payment: ' . $e->getMessage());
        }
    }

    /**
     * Display reconciliation dashboard
     */
    public function reconciliation(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        $unreconciled = Payment::where('status', 'paid')
            ->where('is_reconciled', false)
            ->get();

        $reconciliationData = [
            'total_unreconciled' => $unreconciled->sum('gross_amount'),
            'count_unreconciled' => $unreconciled->count(),
        ];

        return view('finance.payments.reconciliation', compact('unreconciled', 'reconciliationData'));
    }

    /**
     * Store reconciliation record
     */
    public function storeReconciliation(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        $validated = $request->validate([
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'exists:payments,id',
            'bank_reference' => 'required|string',
            'reconciliation_date' => 'required|date',
        ]);

        try {
            foreach ($validated['payment_ids'] as $paymentId) {
                $payment = Payment::findOrFail($paymentId);
                $payment->update([
                    'is_reconciled' => true,
                    'reconciliation_date' => $validated['reconciliation_date'],
                    'bank_reference' => $validated['bank_reference'],
                ]);
            }

            return redirect()->route('payments.reconciliation')
                ->with('success', 'Payments reconciled successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reconcile: ' . $e->getMessage());
        }
    }
}
