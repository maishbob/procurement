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
            model: 'BudgetLine',
            modelId: $budgetLine->id,
            newValues: [
                'budget_code' => $budgetLine->budget_code,
                'fiscal_year' => $budgetLine->fiscal_year,
                'allocated_amount' => $budgetLine->allocated_amount,
                'status' => $budgetLine->status,
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

        $this->auditService->log(
            action: 'BUDGET_ALLOCATED',
            model: 'BudgetLine',
            modelId: $budgetLine->id,
            oldValues: ['allocated_amount' => $oldAmount],
            newValues: ['allocated_amount' => $newAmount],
            metadata: [
                'reallocated_by' => auth()?->id(),
                'difference' => $newAmount - $oldAmount,
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
            model: 'BudgetLine',
            modelId: $budgetLine->id,
            newValues: ['committed_amount' => $budgetLine->committed_amount],
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
            model: 'BudgetLine',
            modelId: $budgetLine->id,
            newValues: ['spent_amount' => $budgetLine->spent_amount],
            metadata: [
                'utilization_percent' => ($budgetLine->spent_amount / $budgetLine->allocated_amount * 100),
                'available_amount' => $budgetLine->available_amount,
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
            model: 'BudgetLine',
            modelId: $budgetLine->id,
            newValues: ['fiscal_year' => $budgetLine->fiscal_year],
            metadata: [
                'finalized_by' => auth()?->id(),
                'finalization_date' => now()->toDateString(),
                'final_spent_amount' => $budgetLine->spent_amount,
            ]
        );
    }

    /**
     * Handle the BudgetLine "updated" event.
     */
    public function updated(BudgetLine $budgetLine): void
    {
        $oldValues = [];
        $newValues = [];

        foreach ($budgetLine->getChanges() as $key => $value) {
            if (!in_array($key, ['updated_at'])) {
                $oldValues[$key] = $budgetLine->getOriginal($key);
                $newValues[$key] = $value;
            }
        }

        if (!empty($newValues)) {
            $this->auditService->log(
                action: 'BUDGET_LINE_UPDATED',
                model: 'BudgetLine',
                modelId: $budgetLine->id,
                oldValues: $oldValues,
                newValues: $newValues
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
            model: 'BudgetLine',
            modelId: $budgetLine->id,
            oldValues: $budgetLine->getAttributes(),
            metadata: [
                'deleted_by' => auth()?->id(),
                'deletion_date' => now()->toDateString(),
            ]
        );
    }
}
