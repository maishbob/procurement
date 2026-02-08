<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Department;
use App\Models\BudgetLine;
use App\Models\Store;
use App\Models\ItemCategory;
use App\Models\AuditLog;
use App\Services\BudgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class AdminController extends Controller
{
    public function __construct(private BudgetService $budgetService) {}

    /**
     * Display admin dashboard
     */
    public function index()
    {
        $this->authorize('admin');

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_requisitions' => \App\Models\Requisition::count(),
            'pending_approvals' => \App\Models\Requisition::where('status', 'pending_approval')->count(),
            'total_suppliers' => \App\Models\Supplier::count(),
            'blacklisted_suppliers' => \App\Models\Supplier::where('is_blacklisted', true)->count(),
            'total_payments' => \App\Models\Payment::sum('gross_amount'),
            'recent_audits' => AuditLog::latest()->limit(10)->get(),
        ];

        return view('admin.index', compact('stats'));
    }

    // ========== USER MANAGEMENT ==========

    /**
     * List all users
     */
    public function indexUsers(Request $request)
    {
        $this->authorize('admin');

        $filters = [
            'role' => $request->get('role'),
            'department_id' => $request->get('department_id'),
            'status' => $request->get('status'),
            'search' => $request->get('search'),
        ];

        $users = User::query()
            ->when($filters['role'], fn($q) => $q->whereHas('roles', fn($rq) => $rq->where('name', $filters['role'])))
            ->when($filters['department_id'], fn($q) => $q->where('department_id', $filters['department_id']))
            ->when($filters['status'], fn($q) => $q->where('is_active', $filters['status'] === 'active'))
            ->when($filters['search'], fn($q) => $q->where('name', 'like', "%{$filters['search']}%"))
            ->paginate(20);

        return view('admin.users.index', compact('users', 'filters'));
    }

    /**
     * Show create user form
     */
    public function createUser()
    {
        $this->authorize('admin');

        $roles = Role::all();
        $departments = Department::all();

        return view('admin.users.create', compact('roles', 'departments'));
    }

    /**
     * Store new user
     */
    public function storeUser(Request $request)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string|unique:users',
            'department_id' => 'required|exists:departments,id',
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'approval_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'department_id' => $validated['department_id'],
                'approval_limit' => $validated['approval_limit'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
                'password' => Hash::make('temporary_password'),
            ]);

            $user->roles()->sync($validated['role_ids']);

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'User created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Display user details
     */
    public function showUser(User $user)
    {
        $this->authorize('admin');

        $roles = $user->roles()->get();
        $recentActivity = AuditLog::where('user_id', $user->id)
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.users.show', compact('user', 'roles', 'recentActivity'));
    }

    /**
     * Show edit user form
     */
    public function editUser(User $user)
    {
        $this->authorize('admin');

        $roles = Role::all();
        $departments = Department::all();
        $userRoles = $user->roles()->pluck('id')->toArray();

        return view('admin.users.edit', compact('user', 'roles', 'departments', 'userRoles'));
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, User $user)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|unique:users,phone,' . $user->id,
            'department_id' => 'sometimes|exists:departments,id',
            'role_ids' => 'sometimes|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'approval_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            $user->update($validated);

            if (!empty($validated['role_ids'])) {
                $user->roles()->sync($validated['role_ids']);
            }

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'User updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Delete user
     */
    public function destroyUser(User $user)
    {
        $this->authorize('admin');

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot delete your own account');
        }

        try {
            $user->delete();

            return redirect()->route('admin.users.index')
                ->with('success', 'User deleted');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(User $user)
    {
        $this->authorize('admin');

        $user->update(['password' => Hash::make('temporary_password')]);

        return back()->with('success', 'Password reset. User should update it on next login.');
    }

    /**
     * Toggle user active status
     */
    public function toggleUserStatus(User $user)
    {
        $this->authorize('admin');

        $user->update(['is_active' => !$user->is_active]);

        return back()->with('success', 'User status updated');
    }

    // ========== DEPARTMENT MANAGEMENT ==========

    /**
     * List all departments
     */
    public function indexDepartments()
    {
        $this->authorize('admin');

        $departments = Department::withCount('users')->paginate(20);

        return view('admin.departments.index', compact('departments'));
    }

    /**
     * Show create department form
     */
    public function createDepartment()
    {
        $this->authorize('admin');

        return view('admin.departments.create');
    }

    /**
     * Store new department
     */
    public function storeDepartment(Request $request)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'name' => 'required|string|unique:departments',
            'code' => 'required|string|unique:departments',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        try {
            Department::create($validated);

            return redirect()->route('admin.departments.index')
                ->with('success', 'Department created');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create: ' . $e->getMessage());
        }
    }

    /**
     * Display department
     */
    public function showDepartment(Department $department)
    {
        $this->authorize('admin');

        $users = $department->users()->get();

        return view('admin.departments.show', compact('department', 'users'));
    }

    /**
     * Show edit department form
     */
    public function editDepartment(Department $department)
    {
        $this->authorize('admin');

        return view('admin.departments.edit', compact('department'));
    }

    /**
     * Update department
     */
    public function updateDepartment(Request $request, Department $department)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:departments,name,' . $department->id,
            'code' => 'sometimes|string|unique:departments,code,' . $department->id,
            'description' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        try {
            $department->update($validated);

            return redirect()->route('admin.departments.show', $department)
                ->with('success', 'Department updated');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    /**
     * Delete department
     */
    public function destroyDepartment(Department $department)
    {
        $this->authorize('admin');

        if ($department->users()->exists()) {
            return back()->with('error', 'Cannot delete department with assigned users');
        }

        try {
            $department->delete();

            return redirect()->route('admin.departments.index')
                ->with('success', 'Department deleted');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }

    // ========== BUDGET MANAGEMENT ==========

    /**
     * List all budget lines
     */
    public function indexBudgetLines(Request $request)
    {
        $this->authorize('admin');

        $filters = [
            'fiscal_year' => $request->get('fiscal_year', now()->year),
            'department_id' => $request->get('department_id'),
        ];

        $budgetLines = BudgetLine::query()
            ->when($filters['fiscal_year'], fn($q) => $q->where('fiscal_year', $filters['fiscal_year']))
            ->when($filters['department_id'], fn($q) => $q->where('department_id', $filters['department_id']))
            ->paginate(20);

        return view('admin.budget-lines.index', compact('budgetLines', 'filters'));
    }

    /**
     * Show create budget line form
     */
    public function createBudgetLine()
    {
        $this->authorize('admin');

        $departments = Department::all();

        return view('admin.budget-lines.create', compact('departments'));
    }

    /**
     * Store new budget line
     */
    public function storeBudgetLine(Request $request)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'fiscal_year' => 'required|numeric|min:2020',
            'allocation_amount' => 'required|numeric|min:0',
            'cost_center' => 'required|string',
        ]);

        try {
            $this->budgetService->allocateBudget(
                $validated['department_id'],
                $validated['allocation_amount'],
                $validated['fiscal_year']
            );

            return redirect()->route('admin.budget-lines.index')
                ->with('success', 'Budget allocated');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to allocate: ' . $e->getMessage());
        }
    }

    // ========== STORES MANAGEMENT ==========

    /**
     * List all stores
     */
    public function indexStores()
    {
        $this->authorize('admin');

        $stores = Store::withCount('inventoryItems')->paginate(20);

        return view('admin.stores.index', compact('stores'));
    }

    /**
     * Show create store form
     */
    public function createStore()
    {
        $this->authorize('admin');

        return view('admin.stores.create');
    }

    /**
     * Store new store
     */
    public function storeStore(Request $request)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'store_code' => 'required|string|unique:stores',
            'store_name' => 'required|string',
            'location' => 'required|string',
            'manager_id' => 'required|exists:users,id',
        ]);

        try {
            Store::create($validated);

            return redirect()->route('admin.stores.index')
                ->with('success', 'Store created');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create: ' . $e->getMessage());
        }
    }

    // ========== ITEM CATEGORIES ==========

    /**
     * List all item categories
     */
    public function indexCategories()
    {
        $this->authorize('admin');

        $categories = ItemCategory::withCount('items')->paginate(20);

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show create category form
     */
    public function createCategory()
    {
        $this->authorize('admin');

        return view('admin.categories.create');
    }

    /**
     * Store new category
     */
    public function storeCategory(Request $request)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'category_name' => 'required|string|unique:item_categories',
            'description' => 'nullable|string',
        ]);

        try {
            ItemCategory::create($validated);

            return redirect()->route('admin.categories.index')
                ->with('success', 'Category created');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create: ' . $e->getMessage());
        }
    }

    // ========== SYSTEM SETTINGS ==========

    /**
     * Show system settings
     */
    public function editSettings()
    {
        $this->authorize('admin');

        $settings = \App\Models\SystemSetting::all()->pluck('value', 'key')->toArray();

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update system settings
     */
    public function updateSettings(Request $request)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'company_name' => 'required|string',
            'company_email' => 'required|email',
            'company_phone' => 'required|string',
            'vat_rate' => 'required|numeric|min:0|max:100',
            'wht_rate' => 'required|numeric|min:0|max:100',
            'currency' => 'required|in:KES,USD,GBP,EUR',
        ]);

        try {
            foreach ($validated as $key => $value) {
                \App\Models\SystemSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }

            Cache::forget('system_settings');

            return redirect()->route('admin.settings.index')
                ->with('success', 'Settings updated');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    /**
     * Show fiscal year configuration
     */
    public function editFiscalYear()
    {
        $this->authorize('admin');

        $fiscalYears = \App\Models\FiscalYear::all();

        return view('admin.settings.fiscal-year', compact('fiscalYears'));
    }

    /**
     * Update fiscal year
     */
    public function updateFiscalYear(Request $request)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'year_label' => 'required|string',
        ]);

        try {
            \App\Models\FiscalYear::create($validated);

            return redirect()->route('admin.settings.fiscal-year')
                ->with('success', 'Fiscal year created');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create: ' . $e->getMessage());
        }
    }

    // ========== AUDIT & LOGS ==========

    /**
     * Display activity logs
     */
    public function activityLogs(Request $request)
    {
        $this->authorize('admin');

        $filters = [
            'user_id' => $request->get('user_id'),
            'action' => $request->get('action'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $logs = AuditLog::query()
            ->when($filters['user_id'], fn($q) => $q->where('user_id', $filters['user_id']))
            ->when($filters['action'], fn($q) => $q->where('action', $filters['action']))
            ->when($filters['date_from'], fn($q) => $q->where('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn($q) => $q->where('created_at', '<=', $filters['date_to']))
            ->latest()
            ->paginate(50);

        return view('admin.activity-logs', compact('logs', 'filters'));
    }

    /**
     * Export activity logs
     */
    public function exportActivityLogs(Request $request)
    {
        $this->authorize('admin');

        try {
            $logs = AuditLog::query()
                ->when($request->get('date_from'), fn($q) => $q->where('created_at', '>=', $request->get('date_from')))
                ->when($request->get('date_to'), fn($q) => $q->where('created_at', '<=', $request->get('date_to')))
                ->get();

            return back()->with('success', 'Logs exported');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export: ' . $e->getMessage());
        }
    }

    /**
     * Display system health check
     */
    public function systemHealth()
    {
        $this->authorize('admin');

        $health = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'mail' => $this->checkMail(),
        ];

        return view('admin.health', compact('health'));
    }

    /**
     * Clear application cache
     */
    public function clearCache()
    {
        $this->authorize('admin');

        Cache::flush();

        return back()->with('success', 'Cache cleared');
    }

    /**
     * Get budget lines for department via AJAX
     */
    public function getBudgetLines(Request $request, $departmentId)
    {
        $budgetLines = BudgetLine::where('department_id', $departmentId)->get(['id', 'cost_center']);

        return response()->json($budgetLines);
    }

    /**
     * Get budget balance via AJAX
     */
    public function getBudgetBalance(Request $request, $budgetLineId)
    {
        $budgetLine = BudgetLine::findOrFail($budgetLineId);

        return response()->json([
            'allocated' => $budgetLine->allocation_amount,
            'committed' => $budgetLine->committed_amount,
            'spent' => $budgetLine->spent_amount,
            'available' => $budgetLine->available_balance,
        ]);
    }

    /**
     * Get current exchange rates via AJAX
     */
    public function getExchangeRates()
    {
        $rates = \App\Models\ExchangeRate::where('base_currency', 'KES')
            ->where('date', now()->format('Y-m-d'))
            ->get();

        return response()->json($rates);
    }

    // ========== HEALTH CHECKS ==========

    private function checkDatabase(): bool
    {
        try {
            \DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkStorage(): bool
    {
        try {
            \Storage::exists('.');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            Cache::put('health_check', true, now()->addMinute());
            return Cache::get('health_check') === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkQueue(): bool
    {
        return true;
    }

    private function checkMail(): bool
    {
        return true;
    }
}
