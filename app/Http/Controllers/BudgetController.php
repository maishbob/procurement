<?php

namespace App\Http\Controllers;

use App\Models\BudgetApproval;
use App\Models\BudgetLine;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Services\BudgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BudgetController extends Controller
{
    public function __construct(private BudgetService $budgetService) {}

    /**
     * Display a listing of budget lines.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', BudgetLine::class);

        $query = BudgetLine::with(['department'])
            ->withUtilization();

        // Filter by fiscal year
        if ($request->filled('fiscal_year')) {
            $query->forFiscalYear($request->fiscal_year);
        } else {
            // Default to current active fiscal year
            $activeFiscalYear = FiscalYear::where('is_active', true)->first();
            if ($activeFiscalYear) {
                $query->forFiscalYear($activeFiscalYear->name);
            }
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $query->forDepartment($request->department_id);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $budgetLines = $query->orderBy('budget_code')->paginate(15);

        $departments = Department::orderBy('name')->get();
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();

        // Calculate summary statistics
        $summary = [
            'total_allocated' => $budgetLines->sum('allocated_amount'),
            'total_committed' => $budgetLines->sum('committed_amount'),
            'total_spent' => $budgetLines->sum('spent_amount'),
            'total_available' => $budgetLines->sum(function ($line) {
                return $line->available_amount;
            }),
        ];

        return view('budgets.index', compact('budgetLines', 'departments', 'fiscalYears', 'summary'));
    }

    /**
     * Display budget dashboard with categorization.
     */
    public function dashboard(Request $request)
    {
        Gate::authorize('viewAny', BudgetLine::class);

        $fiscalYear = $request->get('fiscal_year', now()->year);

        // Get all budgets for the fiscal year
        $allBudgets = BudgetLine::with(['department'])
            ->forFiscalYear($fiscalYear)
            ->get();

        // Get budgets by department category
        $academicBudgets = BudgetLine::with(['department'])
            ->forFiscalYear($fiscalYear)
            ->whereHas('department', function ($query) {
                $query->where('category', 'Academic');
            })
            ->orderBy('budget_code')
            ->get();

        $operationsBudgets = BudgetLine::with(['department'])
            ->forFiscalYear($fiscalYear)
            ->whereHas('department', function ($query) {
                $query->where('category', 'Operations');
            })
            ->orderBy('budget_code')
            ->get();

        // Calculate totals
        $academicTotal = $academicBudgets->sum('allocated_amount');
        $operationsTotal = $operationsBudgets->sum('allocated_amount');
        $totalAllocated = $allBudgets->sum('allocated_amount');

        // Totals by status
        $approvedAmount = $allBudgets->where('status', 'approved')->sum('allocated_amount');
        $pendingAmount = $allBudgets->where('status', 'pending_review')->sum('allocated_amount');
        $draftAmount = $allBudgets->where('status', 'draft')->sum('allocated_amount');

        // Budget totals by category
        $budgetsByCategory = $allBudgets->groupBy('category')->map(function ($budgets) {
            return $budgets->sum('allocated_amount');
        });

        return view('budgets.dashboard', compact(
            'academicBudgets',
            'operationsBudgets',
            'academicTotal',
            'operationsTotal',
            'totalAllocated',
            'approvedAmount',
            'pendingAmount',
            'draftAmount',
            'budgetsByCategory'
        ));
    }

    /**
     * Show the form for creating a new budget line.
     */
    public function create()
    {
        Gate::authorize('create', BudgetLine::class);

        $departments = Department::orderBy('name')->get();
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $categories = [
            'operational' => 'Operational',
            'capital' => 'Capital',
            'development' => 'Development',
            'emergency' => 'Emergency',
        ];

        return view('budgets.create', compact('departments', 'fiscalYears', 'categories'));
    }

    /**
     * Store a newly created budget line in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', BudgetLine::class);

        $validated = $request->validate([
            'budget_code' => 'required|string|max:50|unique:budget_lines,budget_code',
            'description' => 'required|string|max:255',
            'fiscal_year' => 'required|string|max:20',
            'department_id' => 'required|exists:departments,id',
            'category' => 'required|string|in:operational,capital,development,emergency',
            'allocated_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['committed_amount'] = 0;
        $validated['spent_amount'] = 0;
        $validated['available_amount'] = $validated['allocated_amount'];
        $validated['is_active'] = $request->has('is_active');

        $budgetLine = BudgetLine::create($validated);

        return redirect()
            ->route('budgets.show', $budgetLine)
            ->with('success', 'Budget line created successfully.');
    }

    /**
     * Display the specified budget line.
     */
    public function show(BudgetLine $budget)
    {
        Gate::authorize('view', $budget);

        $budget->load([
            'department',
            'transactions' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'approvals.approver',
            'submitter',
            'approver'
        ]);

        // Get related requisitions
        $requisitions = $budget->requisitions()
            ->with(['creator', 'department'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('budgets.show', compact('budget', 'requisitions'));
    }

    /**
     * Show the form for editing the specified budget line.
     */
    public function edit(BudgetLine $budget)
    {
        Gate::authorize('update', $budget);

        $departments = Department::orderBy('name')->get();
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $categories = [
            'operational' => 'Operational',
            'capital' => 'Capital',
            'development' => 'Development',
            'emergency' => 'Emergency',
        ];

        return view('budgets.edit', compact('budget', 'departments', 'fiscalYears', 'categories'));
    }

    /**
     * Update the specified budget line in storage.
     */
    public function update(Request $request, BudgetLine $budget)
    {
        Gate::authorize('update', $budget);

        $validated = $request->validate([
            'budget_code' => 'required|string|max:50|unique:budget_lines,budget_code,' . $budget->id,
            'description' => 'required|string|max:255',
            'fiscal_year' => 'required|string|max:20',
            'department_id' => 'required|exists:departments,id',
            'category' => 'required|string|in:operational,capital,development,emergency',
            'allocated_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $budget->update($validated);

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Budget line updated successfully.');
    }

    /**
     * Remove the specified budget line from storage.
     */
    public function destroy(BudgetLine $budget)
    {
        Gate::authorize('delete', $budget);

        // Check if budget has been used
        if ($budget->committed_amount > 0 || $budget->spent_amount > 0) {
            return redirect()
                ->route('budgets.index')
                ->with('error', 'Cannot delete budget line that has commitments or expenditures.');
        }

        $budget->delete();

        return redirect()
            ->route('budgets.index')
            ->with('success', 'Budget line deleted successfully.');
    }

    /**
     * Show budget setup page with fiscal years
     */
    public function setup()
    {
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();

        return view('budgets.setup', compact('fiscalYears'));
    }

    /**
     * Show department budget setup for a fiscal year
     */
    public function departmentSetup(Request $request)
    {
        $fiscalYear = $request->get('fiscal_year');

        if (!$fiscalYear) {
            return redirect()->route('budgets.setup')
                ->with('error', 'Please select a fiscal year');
        }

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get existing budgets for this fiscal year
        $existingBudgets = BudgetLine::where('fiscal_year', $fiscalYear)
            ->get()
            ->keyBy('department_id');

        return view('budgets.department-setup', compact('fiscalYear', 'departments', 'existingBudgets'));
    }

    /**
     * Store or update department budgets for a fiscal year
     */
    public function storeDepartmentBudgets(Request $request)
    {
        $validated = $request->validate([
            'fiscal_year' => 'required|string',
            'departments' => 'required|array',
            'departments.*.department_id' => 'required|exists:departments,id',
            'departments.*.category' => 'required|in:operational,capital,development,emergency',
            'departments.*.allocated_amount' => 'required|numeric|min:0',
            'departments.*.is_active' => 'nullable|boolean',
        ]);

        $createdCount = 0;
        $updatedCount = 0;

        foreach ($validated['departments'] as $deptData) {
            $departmentId = $deptData['department_id'];
            $department = Department::find($departmentId);

            // Check if budget line already exists
            $budgetLine = BudgetLine::where('fiscal_year', $validated['fiscal_year'])
                ->where('department_id', $departmentId)
                ->first();

            $budgetData = [
                'fiscal_year' => $validated['fiscal_year'],
                'department_id' => $departmentId,
                'category' => $deptData['category'],
                'allocated_amount' => $deptData['allocated_amount'],
                'is_active' => isset($deptData['is_active']) ? true : false,
                'status' => 'draft',
                'submitted_by' => auth()->id(),
            ];

            if ($budgetLine) {
                // Update existing
                $budgetLine->update($budgetData);
                $updatedCount++;
            } else {
                // Create new
                $budgetData['budget_code'] = 'BUD-' . $validated['fiscal_year'] . '-' . strtoupper(substr($department->name, 0, 3)) . '-' . str_pad($departmentId, 3, '0', STR_PAD_LEFT);
                $budgetData['description'] = 'Annual budget for ' . $department->name;
                $budgetData['committed_amount'] = 0;
                $budgetData['spent_amount'] = 0;
                $budgetData['available_amount'] = $deptData['allocated_amount'];

                BudgetLine::create($budgetData);
                $createdCount++;
            }
        }

        $message = "Budget setup complete: {$createdCount} created, {$updatedCount} updated";

        return redirect()
            ->route('budgets.index')
            ->with('success', $message);
    }

    /**
     * Submit budget for approval.
     */
    public function submit(BudgetLine $budget)
    {
        $this->authorize('submit', $budget);

        if ($budget->status !== 'draft') {
            return back()->with('error', 'Only draft budgets can be submitted for approval.');
        }

        $budget->update([
            'status' => 'pending_review',
            'submitted_at' => now(),
        ]);

        // Create approval record
        BudgetApproval::create([
            'budget_line_id' => $budget->id,
            'approver_id' => auth()->id(),
            'action' => 'submitted',
            'approver_role' => $this->getUserRole(),
            'comments' => 'Budget submitted for review',
        ]);

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Budget submitted for approval.');
    }

    /**
     * Approve budget.
     */
    public function approve(Request $request, BudgetLine $budget)
    {
        $this->authorize('approve', $budget);

        if ($budget->status !== 'pending_review') {
            return back()->with('error', 'Only pending budgets can be approved.');
        }

        $validated = $request->validate([
            'comments' => 'nullable|string|max:1000',
        ]);

        $budget->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Create approval record
        BudgetApproval::create([
            'budget_line_id' => $budget->id,
            'approver_id' => auth()->id(),
            'action' => 'approved',
            'approver_role' => $this->getUserRole(),
            'comments' => $validated['comments'] ?? 'Budget approved',
        ]);

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Budget approved successfully.');
    }

    /**
     * Reject budget.
     */
    public function reject(Request $request, BudgetLine $budget)
    {
        $this->authorize('reject', $budget);

        if ($budget->status !== 'pending_review') {
            return back()->with('error', 'Only pending budgets can be rejected.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $budget->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        // Create approval record
        BudgetApproval::create([
            'budget_line_id' => $budget->id,
            'approver_id' => auth()->id(),
            'action' => 'rejected',
            'approver_role' => $this->getUserRole(),
            'comments' => $validated['rejection_reason'],
        ]);

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Budget rejected.');
    }

    /**
     * Get pending budgets for approval.
     */
    public function pending()
    {
        $role = $this->getUserRole();

        $budgets = BudgetLine::with(['department', 'submitter'])
            ->where('status', 'pending_review')
            ->orderBy('submitted_at', 'desc')
            ->paginate(15);

        return view('budgets.pending', compact('budgets', 'role'));
    }

    /**
     * Show approval form for a budget.
     */
    public function showApproval(BudgetLine $budget)
    {
        if ($budget->status !== 'pending_review') {
            return redirect()
                ->route('budgets.show', $budget)
                ->with('error', 'This budget is not pending approval.');
        }

        $budget->load(['department', 'submitter', 'approvals.approver']);

        return view('budgets.approve', compact('budget'));
    }

    /**
     * Get available budgets for a department (API endpoint).
     */
    public function getDepartmentBudgets(Department $department, Request $request)
    {
        $fiscalYear = $request->get('fiscal_year');

        // If no fiscal year provided or it's just a year number, try to find the matching active fiscal year
        if (!$fiscalYear || is_numeric($fiscalYear)) {
             $activeFiscalYear = FiscalYear::where('is_active', true)->first();
             if ($activeFiscalYear) {
                 $fiscalYear = $activeFiscalYear->name;
             } elseif (is_numeric($fiscalYear)) {
                 $fiscalYear = 'FY ' . $fiscalYear;
             } else {
                 $fiscalYear = 'FY ' . now()->year;
             }
        }

        $budgets = BudgetLine::where('department_id', $department->id)
            ->forFiscalYear($fiscalYear)
            ->where('status', 'approved')
            ->withUtilization()
            ->get()
            ->map(function ($budget) {
                return [
                    'id' => $budget->id,
                    'code' => $budget->budget_code,
                    'name' => $budget->description ?? $budget->budget_code,
                    'category' => $budget->category,
                    'allocated_amount' => $budget->allocated_amount,
                    'available_amount' => $budget->available_amount,
                    'spent_amount' => $budget->spent_amount,
                    'committed_amount' => $budget->committed_amount,
                    'has_sufficient_funds' => $budget->available_amount > 0,
                ];
            });

        return response()->json([
            'department' => [
                'id' => $department->id,
                'name' => $department->name,
                'category' => $department->category,
            ],
            'budgets' => $budgets,
            'fiscal_year' => $fiscalYear,
        ]);
    }

    /**
     * Helper method to determine the user's approver role.
     */
    protected function getUserRole(): string
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['Super Administrator', 'Principal'])) {
            return 'principal';
        } elseif ($user->hasRole('Finance Manager')) {
            return 'finance';
        } elseif ($user->hasRole('Head of Department')) {
            return 'hod';
        }

        return 'finance'; // default
    }
}
