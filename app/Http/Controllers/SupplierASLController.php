<?php

namespace App\Http\Controllers;

use App\Modules\Suppliers\Models\Supplier;
use App\Modules\Suppliers\Models\SupplierDocument;
use App\Modules\Suppliers\Services\SupplierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupplierASLController extends Controller
{
    public function __construct(private SupplierService $supplierService) {}

    /**
     * ASL register — list all suppliers with status filter.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        $status = $request->get('status');

        $suppliers = Supplier::query()
            ->when($status, fn($q) => $q->where('asl_status', $status))
            ->orderByRaw("FIELD(asl_status, 'pending_review', 'approved', 'suspended', 'not_applied', 'removed')")
            ->paginate(20);

        $counts = Supplier::selectRaw('asl_status, count(*) as total')
            ->groupBy('asl_status')
            ->pluck('total', 'asl_status');

        return view('suppliers.asl.index', compact('suppliers', 'counts', 'status'));
    }

    /**
     * Show supplier detail for ASL review.
     */
    public function review(Supplier $supplier)
    {
        $this->authorize('view', $supplier);

        $supplier->load(['documents', 'contacts']);
        $completeness = $this->supplierService->calculateOnboardingCompleteness($supplier);

        return view('suppliers.asl.review', compact('supplier', 'completeness'));
    }

    /**
     * Submit a supplier for ASL review.
     */
    public function submit(Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        try {
            $this->supplierService->submitForASLReview($supplier);
            return back()->with('success', "'{$supplier->display_name}' submitted for ASL review.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Approve supplier for the ASL.
     */
    public function approve(Request $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $request->validate([
            'categories' => ['nullable', 'array'],
        ]);

        try {
            $this->supplierService->approveForASL($supplier, $request->user(), $request->input('categories', []));
            return redirect()->route('suppliers.asl.index')
                ->with('success', "'{$supplier->display_name}' approved and added to the Approved Supplier List.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Suspend a supplier from the ASL.
     */
    public function suspend(Request $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $request->validate(['reason' => ['required', 'string', 'min:10']]);

        try {
            $this->supplierService->suspendFromASL($supplier, $request->reason);
            return redirect()->route('suppliers.asl.index')
                ->with('success', "'{$supplier->display_name}' suspended from the ASL.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove a supplier from the ASL.
     */
    public function remove(Request $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $request->validate(['reason' => ['required', 'string', 'min:10']]);

        try {
            $this->supplierService->removeFromASL($supplier, $request->reason);
            return redirect()->route('suppliers.asl.index')
                ->with('success', "'{$supplier->display_name}' removed from the ASL.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Verify a supplier document.
     */
    public function verifyDocument(Request $request, Supplier $supplier, SupplierDocument $document)
    {
        $this->authorize('update', $supplier);

        $this->supplierService->verifyDocument($document, $request->user());

        return back()->with('success', 'Document verified successfully.');
    }

    // -------------------------------------------------------------------------
    // Onboarding
    // -------------------------------------------------------------------------

    /**
     * Show the onboarding document checklist for a supplier.
     */
    public function onboardingChecklist(Supplier $supplier)
    {
        $this->authorize('view', $supplier);

        $supplier->load('documents');
        $completeness = $this->supplierService->calculateOnboardingCompleteness($supplier);

        return view('suppliers.onboarding.checklist', compact('supplier', 'completeness'));
    }

    /**
     * Show the document upload form.
     */
    public function showUploadForm(Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        return view('suppliers.onboarding.upload', compact('supplier'));
    }

    /**
     * Store an uploaded supplier document.
     */
    public function storeDocument(Request $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $request->validate([
            'document_type' => ['required', 'string', 'in:kra_pin_certificate,tax_compliance_certificate,bank_letter,business_registration,etims_registration,other'],
            'file'          => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'expiry_date'   => ['nullable', 'date', 'after:today'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ]);

        $file     = $request->file('file');
        $path     = Storage::disk('local')->putFile("supplier_documents/{$supplier->id}", $file);

        // Replace existing document of same type, or create new
        $supplier->documents()->updateOrCreate(
            ['document_type' => $request->document_type],
            [
                'file_path'   => $path,
                'file_name'   => $file->getClientOriginalName(),
                'expiry_date' => $request->expiry_date,
                'is_required' => in_array($request->document_type, \App\Modules\Suppliers\Services\SupplierService::REQUIRED_DOCUMENTS),
                'notes'       => $request->notes,
                'verified'    => false, // must be re-verified after replacement
                'verified_by' => null,
                'verified_at' => null,
            ]
        );

        return redirect()->route('suppliers.onboarding.checklist', $supplier)
            ->with('success', 'Document uploaded successfully. It must be verified before ASL approval.');
    }
}
