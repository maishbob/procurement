<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\SupplierInvoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService) {}

    /**
     * Display all invoices with filtering
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', SupplierInvoice::class);

        $filters = [
            'status' => $request->get('status'),
            'supplier_id' => $request->get('supplier_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];

        $invoices = $this->invoiceService->getAllInvoices($filters, 15);

        return view('finance.invoices.index', compact('invoices'));
    }

    /**
     * Show create invoice form
     */
    public function create(Request $request)
    {
        $this->authorize('create', SupplierInvoice::class);

        $grnId = $request->get('grn_id');
        $grn = $grnId ? \App\Models\GRN::findOrFail($grnId) : null;

        return view('finance.invoices.create', compact('grn'));
    }

    /**
     * Store new invoice
     */
    public function store(Request $request)
    {
        $this->authorize('create', SupplierInvoice::class);

        $validated = $request->validate([
            'grn_id' => 'required|exists:grns,id',
            'invoice_number' => 'required|string|unique:supplier_invoices',
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after:invoice_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'currency' => 'required|in:KES,USD,GBP,EUR',
            'line_items.*.description' => 'required|string',
            'line_items.*.quantity' => 'required|numeric',
            'line_items.*.unit_price' => 'required|numeric',
            'line_items.*.amount' => 'required|numeric',
        ]);

        try {
            $invoice = $this->invoiceService->createInvoice($validated, auth()->user());

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create invoice: ' . $e->getMessage());
        }
    }

    /**
     * Display invoice details
     */
    public function show(SupplierInvoice $invoice)
    {
        $this->authorize('view', $invoice);

        $lineItems = $invoice->lineItems()->get();
        $matchStatus = $this->invoiceService->validateThreeWayMatch($invoice);
        $attachments = $invoice->attachments()->get();

        return view('finance.invoices.show', compact('invoice', 'lineItems', 'matchStatus', 'attachments'));
    }

    /**
     * Show edit form for draft invoice
     */
    public function edit(SupplierInvoice $invoice)
    {
        $this->authorize('update', $invoice);

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be edited');
        }

        $lineItems = $invoice->lineItems()->get();

        return view('finance.invoices.edit', compact('invoice', 'lineItems'));
    }

    /**
     * Update draft invoice
     */
    public function update(Request $request, SupplierInvoice $invoice)
    {
        $this->authorize('update', $invoice);

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be updated');
        }

        $validated = $request->validate([
            'invoice_number' => 'sometimes|string|unique:supplier_invoices,invoice_number,' . $invoice->id,
            'due_date' => 'sometimes|date',
            'subtotal' => 'sometimes|numeric|min:0',
            'tax_amount' => 'sometimes|numeric|min:0',
            'total_amount' => 'sometimes|numeric|min:0',
            'line_items.*.description' => 'required|string',
            'line_items.*.quantity' => 'required|numeric',
            'line_items.*.unit_price' => 'required|numeric',
            'line_items.*.amount' => 'required|numeric',
        ]);

        try {
            $updated = $this->invoiceService->updateInvoice($invoice, $validated);

            return redirect()->route('invoices.show', $updated)
                ->with('success', 'Invoice updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update invoice: ' . $e->getMessage());
        }
    }

    /**
     * Delete draft invoice
     */
    public function destroy(SupplierInvoice $invoice)
    {
        $this->authorize('delete', $invoice);

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be deleted');
        }

        try {
            $invoice->delete();

            return redirect()->route('invoices.index')
                ->with('success', 'Invoice deleted');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete invoice: ' . $e->getMessage());
        }
    }

    /**
     * Submit invoice for verification (three-way match)
     */
    public function submit(SupplierInvoice $invoice)
    {
        $this->authorize('view', $invoice);

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be submitted');
        }

        try {
            $this->invoiceService->submitInvoice($invoice, auth()->user());

            return back()->with('success', 'Invoice submitted for verification');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit invoice: ' . $e->getMessage());
        }
    }

    /**
     * Verify invoice (three-way match pass)
     */
    public function verify(Request $request, SupplierInvoice $invoice)
    {
        $this->authorize('verify', $invoice);

        $validated = $request->validate([
            'verified_notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->invoiceService->verifyInvoice(
                $invoice,
                auth()->user(),
                $validated['verified_notes'] ?? ''
            );

            return back()->with('success', 'Invoice verified successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to verify invoice: ' . $e->getMessage());
        }
    }

    /**
     * Reject invoice (three-way match fail)
     */
    public function reject(Request $request, SupplierInvoice $invoice)
    {
        $this->authorize('verify', $invoice);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        try {
            $this->invoiceService->rejectInvoice(
                $invoice,
                auth()->user(),
                $validated['rejection_reason']
            );

            return back()->with('success', 'Invoice rejected. Awaiting correction from supplier.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject invoice: ' . $e->getMessage());
        }
    }

    /**
     * Show invoice verification form
     */
    public function verifyForm(SupplierInvoice $invoice)
    {
        $this->authorize('verify', $invoice);

        $matchStatus = $this->invoiceService->validateThreeWayMatch($invoice);
        $po = $invoice->grn->purchaseOrder;
        $grn = $invoice->grn;

        return view('finance.invoices.verify', compact('invoice', 'matchStatus', 'po', 'grn'));
    }

    /**
     * Get three-way match validation status
     */
    public function threeWayMatch(SupplierInvoice $invoice)
    {
        $status = $this->invoiceService->validateThreeWayMatch($invoice);

        return response()->json($status);
    }

    /**
     * Upload invoice attachment
     */
    public function uploadAttachment(Request $request, SupplierInvoice $invoice)
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'attachment' => 'required|file|max:5120',
            'type' => 'required|in:invoice,receipt,delivery_note,other',
        ]);

        try {
            $path = $request->file('attachment')->store('invoice-attachments', 'private');

            $invoice->attachments()->create([
                'file_path' => $path,
                'file_type' => $validated['type'],
                'original_filename' => $request->file('attachment')->getClientOriginalName(),
            ]);

            return back()->with('success', 'Attachment uploaded successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to upload attachment: ' . $e->getMessage());
        }
    }

    /**
     * Delete invoice attachment
     */
    public function deleteAttachment(SupplierInvoice $invoice, $attachmentId)
    {
        $this->authorize('update', $invoice);

        try {
            $attachment = $invoice->attachments()->findOrFail($attachmentId);
            $attachment->delete();

            return back()->with('success', 'Attachment deleted');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete attachment: ' . $e->getMessage());
        }
    }

    /**
     * Validate three-way match via AJAX
     */
    public function validateThreeWayMatch(Request $request, SupplierInvoice $invoice)
    {
        try {
            $result = $this->invoiceService->validateThreeWayMatch($invoice);

            return response()->json([
                'status' => $result['match'] ? 'passed' : 'failed',
                'details' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download invoice as PDF
     */
    public function downloadPDF(SupplierInvoice $invoice)
    {
        $this->authorize('view', $invoice);

        try {
            $lineItems = $invoice->lineItems()->get();

            $pdf = Pdf::loadView('finance.invoices.pdf', compact('invoice', 'lineItems'))
                ->setPaper('a4')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10);

            return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }
}
