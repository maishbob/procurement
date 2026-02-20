use App\Events\BudgetThresholdExceededEvent;
<?php

namespace App\Services;

use App\Core\Audit\AuditService;
use App\Models\BudgetLine;
use App\Models\BudgetTransaction;
use App\Models\CostCenter;
use App\Models\Department;

class BudgetService
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Allocate budget to a cost center for a fiscal year
     */
    public function allocateBudget(CostCenter $costCenter, string $fiscalYear, float $amount, bool $isOperational = true): BudgetLine
    {
        $budgetLine = BudgetLine::create([
            'cost_center_id' => $costCenter->id,
            'fiscal_year' => $fiscalYear,
            'allocated_amount' => $amount,
            'committed_amount' => 0,
            'spent_amount' => 0,
            'is_operational' => $isOperational,
        ]);

        $this->auditService->log(
            action: 'BUDGET_ALLOCATED',
            model: 'BudgetLine',
            modelId: $budgetLine->id,
            metadata: [
                'description' => "Budget allocated to {$costCenter->name} for fiscal year {$fiscalYear}: KES " . number_format($amount, 2),
            ],
        );

        return $budgetLine;
    }

    /**
     * Reallocate budget between cost centers or fiscal periods
     */
    public function reallocateBudget(BudgetLine $from, BudgetLine $to, float $amount): void
    {
        if ($from->available_amount < $amount) {
            throw new \Exception("Insufficient available budget to reallocate");
        }

        $fromName = $from->costCenter?->name ?? $from->department?->name ?? "Budget Line #{$from->id}";
        $toName   = $to->costCenter?->name   ?? $to->department?->name   ?? "Budget Line #{$to->id}";

        $from->decrement('allocated_amount', $amount);
        $to->increment('allocated_amount', $amount);

        $this->recordBudgetTransaction($from, 'reallocation_out', $amount, "Reallocated to {$toName}");
        $this->recordBudgetTransaction($to, 'reallocation_in', $amount, "Reallocated from {$fromName}");

        $this->auditService->log(
            action: 'BUDGET_REALLOCATED',
            model: 'BudgetLine',
            modelId: $from->id,
            metadata: [
                'description' => "KES " . number_format($amount, 2) . " reallocated from {$fromName} to {$toName}",
            ],
        );
    }

    /**
     * Commit budget (reserve for requisitions/POs that haven't been paid yet)
     */
    public function commitBudget(BudgetLine $budgetLine, float $amount, string $referenceType, int $referenceId): void
    {
        if ($budgetLine->available_amount < $amount) {
            throw new \Exception("Insufficient available budget. Requested: {$amount}, Available: {$budgetLine->available_amount}");
        }

        $budgetLine->increment('committed_amount', $amount);
        $budgetLine->refresh();

        $this->recordBudgetTransaction($budgetLine, 'commitment', $amount, "{$referenceType} #{$referenceId}");

        $this->auditService->log(
            action: 'BUDGET_COMMITTED',
            model: 'BudgetLine',
            modelId: $budgetLine->id,
            metadata: [
                'description' => "{$referenceType} #{$referenceId} committed KES " . number_format($amount, 2),
            ],
        );
    }

    /**
     * Release committed budget (when requisition is cancelled, etc).
     * Safely releases only up to the currently committed amount to prevent
     * negative committed_amount values when price variances occur.
     */
    public function releaseCommitment(BudgetLine $budgetLine, float $amount, string $reason): void
    {
        $toRelease = min($amount, (float) $budgetLine->committed_amount);

        if ($toRelease <= 0) {
            return;
        }

        $budgetLine->decrement('committed_amount', $toRelease);

        $this->recordBudgetTransaction($budgetLine, 'commitment_release', $toRelease, $reason);

        $this->auditService->log(
            action: 'BUDGET_COMMITMENT_RELEASED',
            model: 'BudgetLine',
            modelId: $budgetLine->id,
            metadata: [
                'description' => "Commitment released: KES " . number_format($toRelease, 2) . ". Reason: {$reason}",
            ],
        );
    }

    /**
     * Record actual spending against budget.
     *
     * Handles price variances between committed (PO) amount and actual invoice
     * amount: commitment is released up to the committed balance, and the actual
     * invoice amount is recorded as spent.
     */
    public function recordExpenditure(BudgetLine $budgetLine, float $amount, string $referenceType, int $referenceId): void
    {
        // Release commitment safely — capped at current committed balance so a
        // price variance (invoice > PO) does not crash with an exception.
        $this->releaseCommitment(
            $budgetLine,
            $amount,
            "Released due to actual payment for {$referenceType} #{$referenceId}"
        );

        // Record actual amount as spent
        $budgetLine->increment('spent_amount', $amount);

        $this->recordBudgetTransaction($budgetLine, 'expenditure', $amount, "{$referenceType} #{$referenceId}");

        // Log a warning when utilisation exceeds 90% threshold
        if ($budgetLine->fresh()->utilization_percentage > 90) {
            \Log::warning('[BudgetService] Budget line nearing limit (>90%)', [
                'budget_line_id'  => $budgetLine->id,
                'utilization_pct' => $budgetLine->utilization_percentage,
                'department_id'   => $budgetLine->department_id,
                'fiscal_year'     => $budgetLine->fiscal_year,
            ]);
            event(new BudgetThresholdExceededEvent(
                $budgetLine,
                $budgetLine->utilization_percentage,
                '90%'
            ));
        }

        $this->auditService->log(
            action: 'BUDGET_EXECUTED',
            model: 'BudgetLine',
            modelId: $budgetLine->id,
            metadata: [
                'description' => "Expenditure recorded: KES " . number_format($amount, 2),
            ],
        );
    }

    /**
     * Record budget transaction (for audit trail)
     */
    private function recordBudgetTransaction(BudgetLine $budgetLine, string $type, float $amount, string $description): BudgetTransaction
    {
        return $budgetLine->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'recorded_by' => auth()?->id(),
        ]);
    }

    /**
     * Get budget execution report for a department
     */
    public function getDepartmentBudgetReport(Department $department, string $fiscalYear): array
    {
        // Query by department_id (the canonical relationship on BudgetLine)
        $budgetLines = BudgetLine::where('department_id', $department->id)
            ->where('fiscal_year', $fiscalYear)
            ->get();

        $totalAllocated = $budgetLines->sum('allocated_amount');
        $totalCommitted = $budgetLines->sum('committed_amount');
        $totalSpent     = $budgetLines->sum('spent_amount');
        $totalAvailable = $budgetLines->sum('available_amount');

        return [
            'department'          => $department,
            'fiscal_year'         => $fiscalYear,
            'total_allocated'     => $totalAllocated,
            'total_committed'     => $totalCommitted,
            'total_spent'         => $totalSpent,
            'total_available'     => $totalAvailable,
            'utilization_percent' => $totalAllocated > 0 ? ($totalSpent / $totalAllocated * 100) : 0,
            'commitment_percent'  => $totalAllocated > 0 ? ($totalCommitted / $totalAllocated * 100) : 0,
            'budget_lines'        => $budgetLines->map(function ($line) {
                // Use cost center name when available, fall back to department / description
                $label = $line->costCenter?->name
                    ?? $line->department?->name
                    ?? $line->description
                    ?? "Budget Line #{$line->id}";

                return [
                    'cost_center' => $label,
                    'category'    => $line->category,
                    'allocated'   => $line->allocated_amount,
                    'committed'   => $line->committed_amount,
                    'spent'       => $line->spent_amount,
                    'available'   => $line->available_amount,
                    'utilization' => $line->utilization_percentage,
                ];
            }),
        ];
    }

    /**
     * Get budget variance analysis
     */
    public function getVarianceAnalysis(string $fiscalYear): array
    {
        $budgetLines = BudgetLine::where('fiscal_year', $fiscalYear)->get();

        return $budgetLines->map(function ($line) {
            $variance        = $line->allocated_amount - $line->spent_amount;
            $variancePercent = $line->allocated_amount > 0 ? ($variance / $line->allocated_amount * 100) : 0;

            // Use cost center name when available, fall back gracefully
            $label = $line->costCenter?->name
                ?? $line->department?->name
                ?? $line->description
                ?? "Budget Line #{$line->id}";

            return [
                'cost_center'      => $label,
                'allocated'        => $line->allocated_amount,
                'spent'            => $line->spent_amount,
                'variance'         => $variance,
                'variance_percent' => $variancePercent,
                'status'           => $variancePercent > 10 ? 'underspent'
                    : (abs($variancePercent) <= 10 ? 'on_track' : 'overspent'),
            ];
        })->sortBy('variance_percent')->values()->toArray();
    }

    /**
     * Check if budget is available for a cost center
     */
    public function isBudgetAvailable(CostCenter $costCenter, float $amount, string $fiscalYear): bool
    {
        $budgetLine = BudgetLine::where('cost_center_id', $costCenter->id)
            ->where('fiscal_year', $fiscalYear)
            ->first();

        if (!$budgetLine) {
            return false;
        }

        return $budgetLine->available_amount >= $amount;
    }

    /**
     * Finalize budget for fiscal year (lock from further changes)
     */
    public function finalizeBudget(string $fiscalYear): void
    {
        BudgetLine::where('fiscal_year', $fiscalYear)->update(['is_final' => true]);

        $this->auditService->log(
            action: 'BUDGET_YEAR_FINALIZED',
            model: 'BudgetLine',
            modelId: 0,
            metadata: [
                'description' => "All budgets for fiscal year {$fiscalYear} have been finalized and locked",
            ],
        );
    }

    /**
     * Get budget execution by category (operational vs capital)
     */
    public function getBudgetByCategory(string $fiscalYear): array
    {
        $operational = BudgetLine::where('fiscal_year', $fiscalYear)
            ->where('is_operational', true)
            ->get();

        $capital = BudgetLine::where('fiscal_year', $fiscalYear)
            ->where('is_operational', false)
            ->get();

        return [
            'operational' => [
                'allocated' => $operational->sum('allocated_amount'),
                'spent'     => $operational->sum('spent_amount'),
                'available' => $operational->sum('available_amount'),
            ],
            'capital' => [
                'allocated' => $capital->sum('allocated_amount'),
                'spent'     => $capital->sum('spent_amount'),
                'available' => $capital->sum('available_amount'),
            ],
        ];
    }
}
