<?php

namespace App\Policies;

use App\Models\BudgetLine;
use App\Models\User;

class BudgetLinePolicy
{
    /**
     * Determine if the user can view any budget lines
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('budget.view') || 
               $user->hasAnyRole(['finance-manager', 'admin', 'super-admin', 'principal', 'hod', 'department-head', 'requisitioner']);
    }

    /**
     * Determine if the user can view a specific budget line
     */
    public function view(User $user, BudgetLine $budgetLine): bool
    {
        // Finance/Admin can view all budgets
        if ($user->hasAnyRole(['finance-manager', 'admin', 'super-admin', 'principal'])) {
            return true;
        }

        // Department heads / HODs can view their department's budgets
        // Check both department_id directly and via cost_center if available
        $isMyDepartment = $budgetLine->department_id === $user->department_id;
        
        if (($user->hasRole('department-head') || $user->hasRole('hod')) && $isMyDepartment) {
            return true;
        }

        // Requisitioners/Staff can view budgets if they're in the same department
        if (($user->hasRole('requisitioner') || $user->hasRole('staff')) && $isMyDepartment) {
            return true;
        }

        // Budget owners
        if ($user->id === $budgetLine->submitted_by) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create a budget line (finance only)
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['finance-manager', 'admin', 'super-admin'])
            && $user->hasPermission('budget.manage');
    }

    /**
     * Determine if the user can update a budget line (fiscal year must be draft)
     */
    public function update(User $user, BudgetLine $budgetLine): bool
    {
        // Can't update if fiscal year has been finalized
        if ($budgetLine->is_final) {
            return false;
        }

        return $user->hasAnyRole(['finance-manager', 'admin', 'super-admin'])
            && $user->hasPermission('budget.manage');
    }

    /**
     * Determine if the user can allocate/reallocate budget (approval required for large amounts)
     */
    public function allocate(User $user, BudgetLine $budgetLine): bool
    {
        if ($budgetLine->is_final) {
            return false;
        }

        // Check allocation authority based on user's approval limit
        $userApprovalLimit = $user->approval_limit ?? 0;
        if ($budgetLine->allocated_amount > $userApprovalLimit && !$user->hasRole('super-admin')) {
            return false;
        }

        return $user->hasAnyRole(['finance-manager', 'admin', 'super-admin'])
            && $user->hasPermission('budget.approve');
    }

    /**
     * Determine if the user can delete a budget line (only if no transactions)
     */
    public function delete(User $user, BudgetLine $budgetLine): bool
    {
        // Can't delete if already committed or spent
        if ($budgetLine->committed_amount > 0 || $budgetLine->spent_amount > 0) {
            return false;
        }

        return $user->hasRole('super_admin');
    }

    /**
     * Determine if the user can view budget execution report
     */
    public function viewExecutionReport(User $user, BudgetLine $budgetLine): bool
    {
        return $user->hasAnyRole(['finance-manager', 'procurement-officer', 'admin', 'super-admin'])
            || ($user->hasRole('department-head') && $budgetLine->cost_center?->department_id === $user->department_id);
    }

    /**
     * Determine if the user can view budget variance analysis
     */
    public function viewVarianceAnalysis(User $user): bool
    {
        return $user->hasAnyRole(['finance-manager', 'admin', 'super-admin']);
    }

    /**
     * Determine if the user can finalize/lock budget for fiscal year
     */
    public function finalizeBudget(User $user, BudgetLine $budgetLine): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Determine if the user can export budget data
     */
    public function export(User $user): bool
    {
        return $user->hasAnyRole(['finance-manager', 'admin', 'super-admin']);
    }
}
