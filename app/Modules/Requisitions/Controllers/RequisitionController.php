<?php

namespace App\Modules\Requisitions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Requisitions\Models\Requisition;
use App\Modules\Requisitions\Services\RequisitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * Requisition Controller
 * 
 * Thin HTTP controller delegating to service layer
 */
class RequisitionController extends Controller
{
    protected RequisitionService $requisitionService;

    public function __construct(RequisitionService $requisitionService)
    {
        $this->requisitionService = $requisitionService;

        // Apply middleware
        $this->middleware('auth');
        $this->middleware('permission:requisitions.view')->only(['index', 'show']);
        $this->middleware('permission:requisitions.create')->only(['create', 'store']);
        $this->middleware('permission:requisitions.update')->only(['edit', 'update']);
        $this->middleware('permission:requisitions.submit')->only(['submit']);
        $this->middleware('permission:requisitions.approve')->only(['approve']);
        $this->middleware('permission:requisitions.reject')->only(['reject']);
    }

    /**
     * Display a listing of requisitions
     */
    public function index(Request $request)
    {
        $query = Requisition::with(['department', 'requester', 'items']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by department
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('requisition_number', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%");
            });
        }

        // Apply authorization filters
        $user = Auth::user();
        if (!$user->can('requisitions.view_all')) {
            // Users can only see their department's requisitions
            $query->where('department_id', $user->department_id);
        }

        $requisitions = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('requisitions.index', compact('requisitions'));
    }

    /**
     * Show the form for creating a new requisition
     */
    public function create()
    {
        return view('requisitions.create');
    }

    /**
     * Store a newly created requisition
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'purpose' => 'required|string|max:500',
            'justification' => 'required|string',
            'priority' => 'required|in:low,normal,high,urgent',
            'currency' => 'required|in:KES,USD,GBP,EUR',
            'estimated_total' => 'required|numeric|min:0',
            'budget_line_id' => 'nullable|exists:budget_lines,id',
            'required_by_date' => 'nullable|date|after:today',
            'is_emergency' => 'boolean',
            'emergency_justification' => 'required_if:is_emergency,true',
            'is_single_source' => 'boolean',
            'single_source_justification' => 'required_if:is_single_source,true',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.specifications' => 'nullable|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_of_measure' => 'required|string',
            'items.*.estimated_unit_price' => 'required|numeric|min:0',
            'items.*.is_vatable' => 'boolean',
            'items.*.vat_type' => 'required|in:vatable,zero_rated,exempt',
        ]);

        try {
            $requisition = $this->requisitionService->create($validated);

            return redirect()
                ->route('requisitions.show', $requisition)
                ->with('success', 'Requisition created successfully');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create requisition: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified requisition
     */
    public function show(Requisition $requisition)
    {
        // Authorization check
        $this->authorize('view', $requisition);

        $requisition->load([
            'department',
            'requester',
            'items',
            'approvals.approver',
            'budgetLine'
        ]);

        return view('requisitions.show', compact('requisition'));
    }

    /**
     * Show the form for editing the requisition
     */
    public function edit(Requisition $requisition)
    {
        // Authorization check
        $this->authorize('update', $requisition);

        if (!$requisition->isEditable()) {
            return redirect()
                ->route('requisitions.show', $requisition)
                ->with('error', 'Requisition cannot be edited in current state');
        }

        return view('requisitions.edit', compact('requisition'));
    }

    /**
     * Update the specified requisition
     */
    public function update(Request $request, Requisition $requisition)
    {
        // Authorization check
        $this->authorize('update', $requisition);

        $validated = $request->validate([
            'purpose' => 'required|string|max:500',
            'justification' => 'required|string',
            'priority' => 'required|in:low,normal,high,urgent',
            'estimated_total' => 'required|numeric|min:0',
            'required_by_date' => 'nullable|date|after:today',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.estimated_unit_price' => 'required|numeric|min:0',
        ]);

        try {
            $requisition = $this->requisitionService->update($requisition, $validated);

            return redirect()
                ->route('requisitions.show', $requisition)
                ->with('success', 'Requisition updated successfully');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update requisition: ' . $e->getMessage());
        }
    }

    /**
     * Submit requisition for approval
     */
    public function submit(Requisition $requisition)
    {
        // Authorization check
        $this->authorize('submit', $requisition);

        try {
            $requisition = $this->requisitionService->submit($requisition);

            return redirect()
                ->route('requisitions.show', $requisition)
                ->with('success', 'Requisition submitted for approval');
        } catch (Exception $e) {
            return back()
                ->with('error', 'Failed to submit requisition: ' . $e->getMessage());
        }
    }

    /**
     * Approve requisition
     */
    public function approve(Request $request, Requisition $requisition)
    {
        // Authorization check
        $this->authorize('approve', $requisition);

        $validated = $request->validate([
            'level' => 'required|in:hod,budget_owner,principal,deputy_principal,board',
            'comments' => 'nullable|string|max:1000',
        ]);

        try {
            $requisition = $this->requisitionService->approve(
                $requisition,
                $validated['level'],
                $validated['comments'] ?? null
            );

            return redirect()
                ->route('requisitions.show', $requisition)
                ->with('success', 'Requisition approved successfully');
        } catch (Exception $e) {
            return back()
                ->with('error', 'Failed to approve requisition: ' . $e->getMessage());
        }
    }

    /**
     * Reject requisition
     */
    public function reject(Request $request, Requisition $requisition)
    {
        // Authorization check
        $this->authorize('reject', $requisition);

        $validated = $request->validate([
            'level' => 'required|in:hod,budget_owner,principal,deputy_principal,board',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $requisition = $this->requisitionService->reject(
                $requisition,
                $validated['reason'],
                $validated['level']
            );

            return redirect()
                ->route('requisitions.show', $requisition)
                ->with('success', 'Requisition rejected');
        } catch (Exception $e) {
            return back()
                ->with('error', 'Failed to reject requisition: ' . $e->getMessage());
        }
    }

    /**
     * Cancel requisition
     */
    public function cancel(Request $request, Requisition $requisition)
    {
        // Authorization check
        $this->authorize('cancel', $requisition);

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $requisition = $this->requisitionService->cancel($requisition, $validated['reason']);

            return redirect()
                ->route('requisitions.show', $requisition)
                ->with('success', 'Requisition cancelled');
        } catch (Exception $e) {
            return back()
                ->with('error', 'Failed to cancel requisition: ' . $e->getMessage());
        }
    }

    /**
     * Export requisitions to Excel
     */
    public function export(Request $request)
    {
        // Authorization check
        $this->authorize('export', Requisition::class);

        // Implementation would use maatwebsite/excel
        // to export filtered requisitions
    }

    /**
     * Generate requisition PDF
     */
    public function pdf(Requisition $requisition)
    {
        // Authorization check
        $this->authorize('view', $requisition);

        $requisition->load(['department', 'requester', 'items', 'approvals.approver']);

        // Implementation would use barryvdh/laravel-dompdf
        // to generate PDF
    }
}
