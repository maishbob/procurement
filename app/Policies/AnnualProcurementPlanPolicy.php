<?php

namespace App\Policies;

use App\Modules\Planning\Models\AnnualProcurementPlan;
use App\Models\User;

class AnnualProcurementPlanPolicy
{
    /**
     * Determine if user can reject the plan
     */
    public function reject(User $user, AnnualProcurementPlan $plan): bool
    {
        if (!$plan->canBeRejected()) {
            return false;
        }
        // Approvers must be Principal or Board
        return $user->hasPermission('approve_annual_procurement_plans');
    }
    /**
     * Determine if user can view any plans
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'view_annual_procurement_plans',
            'manage_annual_procurement_plans'
        ]);
    }

    /**
     * Determine if user can view the plan
     */
    public function view(User $user, AnnualProcurementPlan $plan): bool
    {
        return $user->hasAnyPermission([
            'view_annual_procurement_plans',
            'manage_annual_procurement_plans'
        ]);
    }

    /**
     * Determine if user can create plans
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_annual_procurement_plans');
    }

    /**
     * Determine if user can update the plan
     */
    public function update(User $user, AnnualProcurementPlan $plan): bool
    {
        // Can only update draft or active plans
        if (!in_array($plan->status, ['draft', 'active'])) {
            return false;
        }

        return $user->hasPermission('manage_annual_procurement_plans');
    }

    /**
     * Determine if user can submit plan for review
     */
    public function submit(User $user, AnnualProcurementPlan $plan): bool
    {
        return $plan->canBeSubmitted() &&
            $user->hasPermission('manage_annual_procurement_plans');
    }

    /**
     * Determine if user can review the plan
     */
    public function review(User $user, AnnualProcurementPlan $plan): bool
    {
        if (!$plan->canBeReviewed()) {
            return false;
        }

        // Reviewers must be HOD or higher
        return $user->hasAnyPermission([
            'review_annual_procurement_plans',
            'approve_annual_procurement_plans'
        ]);
    }

    /**
     * Determine if user can approve the plan
     */
    public function approve(User $user, AnnualProcurementPlan $plan): bool
    {
        if (!$plan->canBeApproved()) {
            return false;
        }

        // Approvers must be Principal or Board
        return $user->hasPermission('approve_annual_procurement_plans');
    }

    /**
     * Determine if user can activate the plan
     */
    public function activate(User $user, AnnualProcurementPlan $plan): bool
    {
        return $plan->isApproved() &&
            $user->hasPermission('approve_annual_procurement_plans');
    }

    /**
     * Determine if user can perform quarterly review
     */
    public function quarterlyReview(User $user, AnnualProcurementPlan $plan): bool
    {
        return $plan->isActive() &&
            $user->hasAnyPermission([
                'review_annual_procurement_plans',
                'approve_annual_procurement_plans'
            ]);
    }

    /**
     * Determine if user can delete the plan
     */
    public function delete(User $user, AnnualProcurementPlan $plan): bool
    {
        // Can only delete draft plans
        return $plan->isDraft() &&
            $user->hasPermission('manage_annual_procurement_plans');
    }
}
