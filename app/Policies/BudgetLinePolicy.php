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
        return $user->hasPermission('budget.view');
    }

    /**
     * Determine if the user can view a specific budget line
     */
    public function view(User $user, BudgetLine $budgetLine): bool
    {
        // Finance can view all budgets
        if ($user->hasAnyRole(['finance_manager', 'admin', 'super_admin'])) {
            return true;
        }

        // Department heads can view their department's budgets
        if ($user->hasRole('department_head') && $budgetLine->cost_center?->department_id === $user->department_id) {
            return true;
        }

        // Requisitioners can view budgets if they're in the same department
        if ($user->hasRole('requisitioner') && $budgetLine->cost_center?->department_id === $user->department_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create a budget line (finance only)
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['finance_manager', 'admin', 'super_admin'])
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

        return $user->hasAnyRole(['finance_manager', 'admin', 'super_admin'])
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
        if ($budgetLine->allocated_amount > $userApprovalLimit && !$user->hasRole('super_admin')) {
            return false;
        }

        return $user->hasAnyRole(['finance_manager', 'admin', 'super_admin'])
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
        return $user->hasAnyRole(['finance_manager', 'procurement_officer', 'admin', 'super_admin'])
            || ($user->hasRole('department_head') && $budgetLine->cost_center?->department_id === $user->department_id);
    }

    /**
     * Determine if the user can view budget variance analysis
     */
    public function viewVarianceAnalysis(User $user): bool
    {
        return $user->hasAnyRole(['finance_manager', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can finalize/lock budget for fiscal year
     */
    public function finalizeBudget(User $user, BudgetLine $budgetLine): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine if the user can export budget data
     */
    public function export(User $user): bool
    {
        return $user->hasAnyRole(['finance_manager', 'admin', 'super_admin']);
    }
}
