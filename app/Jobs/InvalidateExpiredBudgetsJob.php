<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\BudgetLine;
use App\Models\FiscalYear;

class InvalidateExpiredBudgetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tries = 2;
    protected int $timeout = 300;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Find all budget lines for expired fiscal years
            $expiredBudgets = BudgetLine::whereHas('fiscalYear', function ($query) {
                $query->where('end_date', '<', now());
            })
                ->where('status', '!=', 'closed')
                ->get();

            $closedCount = 0;

            foreach ($expiredBudgets as $budget) {
                $this->closeBudget($budget);
                $closedCount++;
            }

            // Log successful closure
            \App\Core\Audit\AuditService::log(
                action: 'BUDGETS_EXPIRED_CLOSED',
                status: 'success',
                model_type: 'BudgetLine',
                description: "Closed {$closedCount} expired budget lines",
                metadata: [
                    'budgets_closed' => $closedCount,
                    'timestamp' => now()->toDateTimeString(),
                ]
            );

            // Send notifications to affected departments
            $this->notifyDepartments($expiredBudgets);
        } catch (\Exception $e) {
            \App\Core\Audit\AuditService::log(
                action: 'BUDGETS_EXPIRATION_FAILED',
                status: 'failed',
                model_type: 'BudgetLine',
                description: 'Failed to process expired budgets: ' . $e->getMessage(),
                metadata: [
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }

    /**
     * Close individual budget line
     */
    protected function closeBudget(BudgetLine $budget): void
    {
        // Calculate final status
        $utilization = ($budget->amount_executed ?? 0) / ($budget->amount_allocated ?? 1) * 100;
        $variance = ($budget->amount_allocated ?? 0) - ($budget->amount_executed ?? 0);

        // Update budget status
        $budget->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closing_notes' => json_encode([
                'fiscal_year_end' => $budget->fiscalYear?->end_date?->toDateString(),
                'amount_allocated' => $budget->amount_allocated,
                'amount_committed' => $budget->amount_committed,
                'amount_executed' => $budget->amount_executed,
                'utilization_percent' => round($utilization, 2),
                'variance' => $variance,
            ]),
        ]);

        // Audit log
        \App\Core\Audit\AuditService::log(
            action: 'BUDGET_CLOSED',
            status: 'success',
            model_type: 'BudgetLine',
            model_id: $budget->id,
            description: "Budget line '{$budget->description}' closed for expired fiscal year",
            metadata: [
                'budget_id' => $budget->id,
                'department_id' => $budget->department_id,
                'amount_allocated' => $budget->amount_allocated,
                'amount_executed' => $budget->amount_executed,
                'utilization' => round($utilization, 2) . '%',
            ]
        );
    }

    /**
     * Notify departments about budget closure
     */
    protected function notifyDepartments($expiredBudgets): void
    {
        $departments = $expiredBudgets->pluck('department_id')->unique();

        foreach ($departments as $deptId) {
            $department = \App\Models\Department::find($deptId);
            $deptBudgets = $expiredBudgets->where('department_id', $deptId);

            // Find department head/budget owner
            $budgetOwner = \App\Models\User::where('department_id', $deptId)
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['department_head', 'budget_owner']);
                })
                ->first();

            if ($budgetOwner) {
                dispatch(new SendEmailNotificationJob(
                    $budgetOwner,
                    new \App\Notifications\BudgetExpiredNotification($department, $deptBudgets)
                ));
            }
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'scheduled',
            'budgets',
            'fiscal-year-closeout',
        ];
    }
}
