<?php

namespace App\Observers;

use App\Core\Audit\AuditService;
use App\Models\BudgetLine;

class BudgetLineObserver
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Handle the BudgetLine "created" event.
     */
    public function created(BudgetLine $budgetLine): void
    {
        $this->auditService->log(
            action: 'BUDGET_LINE_CREATED',
            status: 'success',
            model_type: 'BudgetLine',
            model_id: $budgetLine->id,
            description: "Budget line created for {$budgetLine->costCenter->name} in fiscal year {$budgetLine->fiscal_year}",
            changes: [
                'cost_center_id' => $budgetLine->cost_center_id,
                'fiscal_year' => $budgetLine->fiscal_year,
                'allocated_amount' => $budgetLine->allocated_amount,
                'is_operational' => $budgetLine->is_operational,
            ],
            metadata: [
                'created_by' => auth()?->id(),
            ]
        );
    }

    /**
     * Handle the BudgetLine "allocated" event.
     */
    public function allocated(BudgetLine $budgetLine): void
    {
        $oldAmount = $budgetLine->getOriginal('allocated_amount');
        $newAmount = $budgetLine->allocated_amount;
        $difference = $newAmount - $oldAmount;

        $this->auditService->log(
            action: 'BUDGET_ALLOCATED',
            status: 'success',
            model_type: 'BudgetLine',
            model_id: $budgetLine->id,
            description: "Budget reallocated for {$budgetLine->costCenter->name}. Change: KES " . number_format($difference, 2),
            changes: ['allocated_amount' => ['from' => $oldAmount, 'to' => $newAmount]],
            metadata: [
                'reallocated_by' => auth()?->id(),
                'previous_amount' => $oldAmount,
                'new_amount' => $newAmount,
            ]
        );
    }

    /**
     * Handle the BudgetLine "committed" event.
     */
    public function committed(BudgetLine $budgetLine): void
    {
        $this->auditService->log(
            action: 'BUDGET_COMMITTED',
            status: 'success',
            model_type: 'BudgetLine',
            model_id: $budgetLine->id,
            description: "Budget commitment recorded. Amount: KES " . number_format($budgetLine->committed_amount, 2),
            metadata: [
                'available_amount' => $budgetLine->available_amount,
            ]
        );
    }

    /**
     * Handle the BudgetLine "executed" event (actual spending).
     */
    public function executed(BudgetLine $budgetLine): void
    {
        $this->auditService->log(
            action: 'BUDGET_EXECUTED',
            status: 'success',
            model_type: 'BudgetLine',
            model_id: $budgetLine->id,
            description: "Budget executed. Spent: KES " . number_format($budgetLine->spent_amount, 2),
            metadata: [
                'utilization_percent' => ($budgetLine->spent_amount / $budgetLine->allocated_amount * 100),
                'remaining_amount' => $budgetLine->available_amount,
            ]
        );
    }

    /**
     * Handle the BudgetLine "finalized" event (fiscal year locked).
     */
    public function finalized(BudgetLine $budgetLine): void
    {
        $this->auditService->log(
            action: 'BUDGET_FINALIZED',
            status: 'success',
            model_type: 'BudgetLine',
            model_id: $budgetLine->id,
            description: "Budget line finalized for fiscal year {$budgetLine->fiscal_year}",
            changes: ['is_final' => ['from' => false, 'to' => true]],
            metadata: [
                'finalized_by' => auth()?->id(),
                'finalization_date' => now(),
                'final_spent_amount' => $budgetLine->spent_amount,
            ]
        );
    }

    /**
     * Handle the BudgetLine "updated" event.
     */
    public function updated(BudgetLine $budgetLine): void
    {
        $changes = [];
        foreach ($budgetLine->getChanges() as $key => $value) {
            if (!in_array($key, ['updated_at'])) {
                $changes[$key] = ['from' => $budgetLine->getOriginal($key), 'to' => $value];
            }
        }

        if (!empty($changes)) {
            $this->auditService->log(
                action: 'BUDGET_LINE_UPDATED',
                status: 'success',
                model_type: 'BudgetLine',
                model_id: $budgetLine->id,
                description: "Budget line updated for {$budgetLine->costCenter->name}",
                changes: $changes
            );
        }
    }

    /**
     * Handle the BudgetLine "deleted" event.
     */
    public function deleted(BudgetLine $budgetLine): void
    {
        $this->auditService->log(
            action: 'BUDGET_LINE_DELETED',
            status: 'success',
            model_type: 'BudgetLine',
            model_id: $budgetLine->id,
            description: "Budget line for {$budgetLine->costCenter->name} permanently deleted",
            changes: ['deleted_by' => auth()?->id()]
        );
    }
}
