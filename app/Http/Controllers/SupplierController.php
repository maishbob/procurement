<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(private SupplierService $supplierService) {}

    /**
     * Display all suppliers with filtering
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        $filters = [
            'status' => $request->get('status'),
            'category_id' => $request->get('category_id'),
            'search' => $request->get('search'),
            'tax_compliance' => $request->get('tax_compliance'),
        ];

        $suppliers = $this->supplierService->getAllSuppliers($filters, 15);

        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Show create supplier form
     */
    public function create()
    {
        $this->authorize('create', Supplier::class);
        $supplier = null;
        return view('suppliers.create', compact('supplier'));
    }

    /**
     * Store new supplier record
     */
    public function store(Request $request)
    {
        $this->authorize('create', Supplier::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:suppliers',
            'kra_pin' => 'required|string|unique:suppliers',
            'tax_file_number' => 'nullable|string|unique:suppliers',
            'email' => 'required|email|unique:suppliers',
            'phone' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
            'supplier_category_id' => 'required|exists:supplier_categories,id',
            'contacts.*.name' => 'required|string',
            'contacts.*.email' => 'required|email',
            'contacts.*.phone' => 'required|string',
        ]);

        try {
            $supplier = $this->supplierService->createSupplier($validated);

            return redirect()->route('suppliers.show', $supplier)
                ->with('success', 'Supplier created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create supplier: ' . $e->getMessage());
        }
    }

    /**
     * Display supplier details
     */
    public function show(Supplier $supplier)
    {
        $this->authorize('view', $supplier);

        $performanceMetrics = $this->supplierService->getPerformanceMetrics($supplier);
        $documents = $supplier->documents()->get();
        $contacts = $supplier->contacts()->get();

        return view('suppliers.show', compact('supplier', 'performanceMetrics', 'documents', 'contacts'));
    }

    /**
     * Show edit supplier form
     */
    public function edit(Supplier $supplier)
    {
        $this->authorize('update', $supplier);
        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update supplier record
     */
    public function update(Request $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email',
            'phone' => 'sometimes|string',
            'address' => 'sometimes|string',
            'city' => 'sometimes|string',
            'country' => 'sometimes|string',
            'tax_compliance_status' => 'sometimes|in:pending,compliant,expired,non-compliant',
        ]);

        try {
            $updated = $this->supplierService->updateSupplier($supplier, $validated);

            return redirect()->route('suppliers.show', $updated)
                ->with('success', 'Supplier updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update supplier: ' . $e->getMessage());
        }
    }

    /**
     * Delete supplier record
     */
    public function destroy(Supplier $supplier)
    {
        $this->authorize('delete', $supplier);

        try {
            $supplier->delete();

            return redirect()->route('suppliers.index')
                ->with('success', 'Supplier deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete supplier: ' . $e->getMessage());
        }
    }

    /**
     * Blacklist supplier
     */
    public function blacklist(Request $request, Supplier $supplier)
    {
        $this->authorize('blacklist', $supplier);

        $validated = $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        try {
            $this->supplierService->blacklistSupplier($supplier, $validated['reason']);

            return back()->with('success', 'Supplier blacklisted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to blacklist supplier: ' . $e->getMessage());
        }
    }

    /**
     * Unblacklist supplier
     */
    public function unblacklist(Supplier $supplier)
    {
        $this->authorize('unblacklist', $supplier);

        try {
            $this->supplierService->unblacklistSupplier($supplier);

            return back()->with('success', 'Supplier reactivated successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reactivate supplier: ' . $e->getMessage());
        }
    }

    /**
     * Display supplier performance metrics
     */
    public function performance(Supplier $supplier)
    {
        $this->authorize('view', $supplier);

        $metrics = $this->supplierService->getPerformanceMetrics($supplier);
        $reviews = $supplier->performanceReviews()->latest()->limit(10)->get();

        return view('suppliers.performance', compact('supplier', 'metrics', 'reviews'));
    }

    /**
     * Display supplier documents
     */
    public function documents(Supplier $supplier)
    {
        $this->authorize('view', $supplier);

        $documents = $supplier->documents()->get();

        return view('suppliers.documents', compact('supplier', 'documents'));
    }

    /**
     * Store document upload
     */
    public function storeDocument(Request $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $validated = $request->validate([
            'document_type' => 'required|string|in:tax_certificate,business_license,kra_compliance,insurance',
            'file' => 'required|file|max:5120',
            'expires_at' => 'nullable|date|after:today',
        ]);

        try {
            $path = $request->file('file')->store('supplier-documents', 'private');

            $this->supplierService->uploadDocument(
                $supplier,
                $validated['document_type'],
                $path,
                $validated['expires_at'] ? \Carbon\Carbon::parse($validated['expires_at']) : null
            );

            // Verify compliance after upload
            $this->supplierService->verifyTaxCompliance($supplier);

            return back()->with('success', 'Document uploaded successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to upload document: ' . $e->getMessage());
        }
    }

    /**
     * Delete document
     */
    public function deleteDocument(Supplier $supplier, $documentId)
    {
        $this->authorize('update', $supplier);

        try {
            $document = $supplier->documents()->findOrFail($documentId);
            $document->delete();

            return back()->with('success', 'Document deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete document: ' . $e->getMessage());
        }
    }

    /**
     * Search suppliers via AJAX
     */
    public function search(Request $request)
    {
        $query = $request->get('q');

        $suppliers = Supplier::where('is_blacklisted', false)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('kra_pin', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'kra_pin', 'email']);

        return response()->json($suppliers);
    }

    /**
     * Get supplier bank details via AJAX
     */
    public function getBankDetails(Supplier $supplier)
    {
        return response()->json([
            'bank_name' => $supplier->bank_name,
            'bank_branch' => $supplier->bank_branch,
            'bank_account_number' => $supplier->bank_account_number,
            'bank_account_name' => $supplier->bank_account_name,
            'bank_swift_code' => $supplier->bank_swift_code,
        ]);
    }

    /**
     * Get supplier performance metrics via AJAX
     */
    public function getPerformanceMetrics(Supplier $supplier)
    {
        $metrics = $this->supplierService->getPerformanceMetrics($supplier);

        return response()->json($metrics);
    }
}
