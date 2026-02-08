<?php

namespace App\Http\Controllers;

use App\Models\Requisition;
use App\Services\RequisitionService;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class RequisitionController extends Controller
{
    public function __construct(
        private RequisitionService $requisitionService,
        private ApprovalService $approvalService
    ) {}

    /**
     * Display paginated list of requisitions
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Requisition::class);

        $filters = [
            'status' => $request->get('status'),
            'department_id' => $request->get('department_id'),
            'created_by' => $request->get('created_by'),
            'search' => $request->get('search'),
        ];

        $requisitions = $this->requisitionService->getAllRequisitions($filters, 15);

        return view('requisitions.index', compact('requisitions'));
    }

    /**
     * Show create requisition form
     */
    public function create()
    {
        $this->authorize('create', Requisition::class);

        $departments = \App\Models\Department::where('is_active', true)->get();
        $categories = \App\Models\ItemCategory::all();
        $suppliers = \App\Models\Supplier::where('is_blacklisted', false)->get();
        $budgetLines = \App\Models\BudgetLine::where('is_active', true)
            ->where('available_amount', '>', 0)
            ->get();

        return view('requisitions.create', compact('departments', 'categories', 'suppliers', 'budgetLines'));
    }

    /**
     * Store new requisition
     */
    public function store(Request $request)
    {
        $this->authorize('create', Requisition::class);

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'purpose' => 'required|string|min:10',
            'justification' => 'required|string|min:10',
            'required_by_date' => 'required|date|after:today',
            'priority' => 'required|in:low,normal,high,urgent',
            'currency' => 'required|in:KES,USD,GBP,EUR',
            'budget_line_id' => 'nullable|exists:budget_lines,id',
            'is_emergency' => 'nullable|boolean',
            'is_single_source' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.specifications' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_of_measure' => 'required|string',
            'items.*.estimated_unit_price' => 'required|numeric|min:0.01',
            'items.*.is_vatable' => 'nullable|boolean',
        ]);

        try {
            $requisition = $this->requisitionService->createRequisition(
                $validated,
                auth()->user()
            );

            return redirect()->route('requisitions.show', $requisition)
                ->with('success', 'Requisition created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create requisition: ' . $e->getMessage());
        }
    }

    /**
     * Display requisition details
     */
    public function show(Requisition $requisition)
    {
        $this->authorize('view', $requisition);

        $items = $requisition->items()->get();
        $approvals = $requisition->approvals()->get();
        $history = $requisition->auditLogs()->orderByDesc('created_at')->get();

        return view('requisitions.show', compact('requisition', 'items', 'approvals', 'history'));
    }

    /**
     * Show edit form for draft requisition
     */
    public function edit(Requisition $requisition)
    {
        $this->authorize('update', $requisition);

        if ($requisition->status !== 'draft') {
            return back()->with('error', 'Only draft requisitions can be edited');
        }

        $departments = \App\Models\Department::where('is_active', true)->get();
        $categories = \App\Models\ItemCategory::all();
        $budgetLines = \App\Models\BudgetLine::where('is_active', true)
            ->where('available_amount', '>', 0)
            ->get();
        $items = $requisition->items()->get();

        return view('requisitions.create', compact('requisition', 'departments', 'categories', 'budgetLines', 'items'));
    }

    /**
     * Update draft requisition
     */
    public function update(Request $request, Requisition $requisition)
    {
        $this->authorize('update', $requisition);

        if ($requisition->status !== 'draft') {
            return back()->with('error', 'Only draft requisitions can be updated');
        }

        $validated = $request->validate([
            'department_id' => 'sometimes|exists:departments,id',
            'purpose' => 'sometimes|string|min:10',
            'justification' => 'sometimes|string|min:10',
            'required_by_date' => 'sometimes|date|after:today',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'currency' => 'sometimes|in:KES,USD,GBP,EUR',
            'budget_line_id' => 'nullable|exists:budget_lines,id',
            'is_emergency' => 'nullable|boolean',
            'is_single_source' => 'nullable|boolean',
            'items' => 'sometimes|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.specifications' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_of_measure' => 'required|string',
            'items.*.estimated_unit_price' => 'required|numeric|min:0.01',
            'items.*.is_vatable' => 'nullable|boolean',
        ]);

        try {
            $updated = $this->requisitionService->updateRequisition($requisition, $validated);

            return redirect()->route('requisitions.show', $updated)
                ->with('success', 'Requisition updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update requisition: ' . $e->getMessage());
        }
    }

    /**
     * Delete draft requisition
     */
    public function destroy(Requisition $requisition)
    {
        $this->authorize('delete', $requisition);

        if ($requisition->status !== 'draft') {
            return back()->with('error', 'Only draft requisitions can be deleted');
        }

        try {
            $requisition->delete();

            return redirect()->route('requisitions.index')
                ->with('success', 'Requisition deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete requisition: ' . $e->getMessage());
        }
    }

    /**
     * Submit requisition for approval
     */
    public function submit(Requisition $requisition)
    {
        $this->authorize('submit', $requisition);

        if ($requisition->status !== 'draft') {
            return back()->with('error', 'Only draft requisitions can be submitted');
        }

        try {
            $this->requisitionService->submitRequisition($requisition);

            return back()->with('success', 'Requisition submitted for approval');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit requisition: ' . $e->getMessage());
        }
    }

    /**
     * Approve requisition (by authorized approver)
     */
    public function approve(Request $request, Requisition $requisition)
    {
        $this->authorize('approve', $requisition);

        $validated = $request->validate([
            'comments' => 'nullable|string|max:500',
        ]);

        try {
            $this->approvalService->approveRequest(
                $requisition,
                auth()->user(),
                $validated['comments'] ?? ''
            );

            return back()->with('success', 'Requisition approved successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to approve requisition: ' . $e->getMessage());
        }
    }

    /**
     * Reject requisition
     */
    public function reject(Request $request, Requisition $requisition)
    {
        $this->authorize('approve', $requisition);

        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ]);

        try {
            $this->approvalService->rejectRequest(
                $requisition,
                auth()->user(),
                $validated['reason']
            );

            return back()->with('success', 'Requisition rejected');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject requisition: ' . $e->getMessage());
        }
    }

    /**
     * Display approval workflow
     */
    public function showApprovals(Requisition $requisition)
    {
        $this->authorize('view', $requisition);

        $approvals = $requisition->approvals()->with('approver')->get();
        $approvalStatus = $this->approvalService->getApprovalStatus($requisition);

        return view('requisitions.approvals', compact('requisition', 'approvals', 'approvalStatus'));
    }

    /**
     * Record approval action
     */
    public function storeApproval(Request $request, Requisition $requisition)
    {
        $this->authorize('approve', $requisition);

        $action = $request->get('action'); // 'approve' or 'reject'

        if ($action === 'approve') {
            return $this->approve($request, $requisition);
        } elseif ($action === 'reject') {
            return $this->reject($request, $requisition);
        }

        return back()->with('error', 'Invalid approval action');
    }

    /**
     * Download requisition as PDF
     */
    public function download(Requisition $requisition)
    {
        $this->authorize('view', $requisition);

        try {
            $items = $requisition->items()->get();
            $approvals = $requisition->approvals()->get();

            $pdf = Pdf::loadView('requisitions.pdf', compact('requisition', 'items', 'approvals'))
                ->setPaper('a4')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10);

            return $pdf->download("requisition-{$requisition->id}.pdf");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }
}
