<?php

namespace App\Observers;

use App\Models\BudgetLine;
use App\Modules\Requisitions\Models\Requisition;
use App\Services\BudgetService;

/**
 * RequisitionObserver
 *
 * Wires budget commitment accounting to the requisition workflow:
 *
 *  - budget_approved  → commitBudget()   (locks funds for this requisition)
 *  - rejected/cancelled from a post-budget state → releaseCommitment()
 *
 * The observer fires on the Eloquent `updating` event (before save) so it
 * can compare getOriginal('status') with the incoming status.
 */
class RequisitionObserver
{
    /**
     * States that occur after budget_approved in the requisition workflow.
     * If a requisition is cancelled or rejected from any of these states,
     * the budget commitment must be released.
     */
    protected const POST_BUDGET_STATES = [
        'budget_approved',
        'procurement_queue',
        'sourcing',
        'quoted',
        'evaluated',
        'awarded',
        'po_created',
        'completed',
    ];

    public function __construct(private BudgetService $budgetService) {}

    /**
     * Handle the Requisition "updating" event.
     * Fires before the record is saved — use getOriginal() for the old value.
     */
    public function updating(Requisition $requisition): void
    {
        $oldStatus = $requisition->getOriginal('status');
        $newStatus = $requisition->status;

        if ($oldStatus === $newStatus) {
            return;
        }

        // Transition INTO budget_approved → commit budget
        if ($newStatus === 'budget_approved') {
            $this->handleBudgetCommit($requisition);
            return;
        }

        // Transition INTO rejected/cancelled FROM a post-budget state → release commitment
        if (
            in_array($newStatus, ['rejected', 'cancelled'], true)
            && in_array($oldStatus, self::POST_BUDGET_STATES, true)
        ) {
            $this->handleBudgetRelease($requisition);
        }
    }

    /**
     * Commit budget when requisition reaches budget_approved.
     */
    protected function handleBudgetCommit(Requisition $requisition): void
    {
        if (!$requisition->budget_line_id) {
            return;
        }

        $budgetLine = BudgetLine::find($requisition->budget_line_id);
        if (!$budgetLine) {
            return;
        }

        $amount = (float) $requisition->estimated_total;
        if ($amount <= 0) {
            return;
        }

        $this->budgetService->commitBudget(
            $budgetLine,
            $amount,
            'Requisition',
            $requisition->id
        );
    }

    /**
     * Release budget commitment when a requisition is rejected or cancelled
     * after the budget was already committed.
     */
    protected function handleBudgetRelease(Requisition $requisition): void
    {
        if (!$requisition->budget_line_id) {
            return;
        }

        $budgetLine = BudgetLine::find($requisition->budget_line_id);
        if (!$budgetLine || $budgetLine->committed_amount <= 0) {
            return;
        }

        $amount = (float) $requisition->estimated_total;
        if ($amount <= 0) {
            return;
        }

        // Never release more than is actually committed (guards against edge cases)
        $releaseAmount = min($amount, (float) $budgetLine->committed_amount);

        $this->budgetService->releaseCommitment(
            $budgetLine,
            $releaseAmount,
            "Requisition #{$requisition->id} {$requisition->status}"
        );
    }
}
