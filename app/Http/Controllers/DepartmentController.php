<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Department::class);

        $query = Department::with(['hod', 'parentDepartment'])
            ->withCount(['users', 'requisitions', 'budgetLines']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by parent
        if ($request->has('top_level') && $request->top_level) {
            $query->topLevel();
        }

        $departments = $query->orderBy('name')->paginate(15);

        // Get all departments for parent selection
        $allDepartments = Department::orderBy('name')->get();

        return view('departments.index', compact('departments', 'allDepartments'));
    }

    /**
     * Show the form for creating a new department.
     */
    public function create()
    {
        Gate::authorize('create', Department::class);

        $users = User::where('is_active', true)->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('departments.create', compact('users', 'departments'));
    }

    /**
     * Store a newly created department in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Department::class);

        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:departments,code',
            'name' => 'required|string|max:255|unique:departments,name',
            'category' => 'required|in:Academic,Operations',
            'description' => 'nullable|string',
            'head_of_department_id' => 'nullable|exists:users,id',
            'parent_department_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $department = Department::create($validated);

        return redirect()
            ->route('departments.show', $department)
            ->with('success', 'Department created successfully.');
    }

    /**
     * Display the specified department.
     */
    public function show(Department $department)
    {
        Gate::authorize('view', $department);

        $department->load([
            'hod',
            'parentDepartment',
            'subDepartments',
            'users' => function ($query) {
                $query->where('is_active', true)->orderBy('name');
            },
            'budgetLines' => function ($query) {
                $query->where('is_active', true)->orderBy('budget_code');
            },
            'requisitions' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }
        ]);

        return view('departments.show', compact('department'));
    }

    /**
     * Show the form for editing the specified department.
     */
    public function edit(Department $department)
    {
        Gate::authorize('update', $department);

        $users = User::where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('id', '!=', $department->id)->orderBy('name')->get();

        return view('departments.edit', compact('department', 'users', 'departments'));
    }

    /**
     * Update the specified department in storage.
     */
    public function update(Request $request, Department $department)
    {
        Gate::authorize('update', $department);

        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:departments,code,' . $department->id,
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
            'category' => 'required|in:Academic,Operations',
            'description' => 'nullable|string',
            'head_of_department_id' => 'nullable|exists:users,id',
            'parent_department_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
        ]);

        // Prevent circular parent relationship
        if ($validated['parent_department_id'] == $department->id) {
            return back()->withErrors(['parent_department_id' => 'A department cannot be its own parent.']);
        }

        $validated['is_active'] = $request->has('is_active');

        $department->update($validated);

        return redirect()
            ->route('departments.show', $department)
            ->with('success', 'Department updated successfully.');
    }

    /**
     * Remove the specified department from storage.
     */
    public function destroy(Department $department)
    {
        Gate::authorize('delete', $department);

        // Check if department has users
        if ($department->users()->count() > 0) {
            return redirect()
                ->route('departments.index')
                ->with('error', 'Cannot delete department with assigned users. Please reassign users first.');
        }

        // Check if department has active budgets
        if ($department->budgetLines()->where('is_active', true)->count() > 0) {
            return redirect()
                ->route('departments.index')
                ->with('error', 'Cannot delete department with active budget lines.');
        }

        // Check if department has sub-departments
        if ($department->subDepartments()->count() > 0) {
            return redirect()
                ->route('departments.index')
                ->with('error', 'Cannot delete department with sub-departments.');
        }

        $department->delete();

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
