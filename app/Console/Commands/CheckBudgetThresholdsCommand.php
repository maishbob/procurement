<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\BudgetThresholdExceededEvent;

class CheckBudgetThresholdsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'procurement:check-budget-thresholds
                            {--threshold=80 : Alert threshold percentage}
                            {--notify : Send notifications to budget owners}
                            {--department= : Filter by department}';

    /**
     * The console command description.
     */
    protected $description = 'Check budget lines exceeding threshold and send alerts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $threshold = (int)$this->option('threshold');
        $notify = $this->option('notify');

        $this->info("Checking budgets exceeding {$threshold}% threshold...");

        try {
            // Get active budget lines for current fiscal year
            $fiscalYear = \App\Models\FiscalYear::where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->first();

            if (!$fiscalYear) {
                $this->warn('No active fiscal year found');
                return self::FAILURE;
            }

            // Find budgets exceeding threshold
            $query = \App\Models\BudgetLine::with(['department'])
                ->where('fiscal_year_id', $fiscalYear->id)
                ->where('status', '!=', 'closed')
                ->where('amount_allocated', '>', 0);

            if ($this->option('department')) {
                $query->where('department_id', $this->option('department'));
            }

            $budgetLines = $query->get();

            $exceeding = $budgetLines->filter(function ($budget) use ($threshold) {
                $utilization = ($budget->amount_executed / $budget->amount_allocated) * 100;
                return $utilization >= $threshold;
            });

            if ($exceeding->isEmpty()) {
                $this->info('✓ No budgets exceed threshold');
                return self::SUCCESS;
            }

            $this->warn("Found {$exceeding->count()} budgets exceeding {$threshold}% threshold");

            // Display budget summary
            $this->table(
                ['Department', 'Budget', 'Allocated', 'Executed', 'Usage %'],
                $exceeding->map(fn($budget) => [
                    $budget->department?->name,
                    $budget->description,
                    'KES ' . number_format($budget->amount_allocated, 2),
                    'KES ' . number_format($budget->amount_executed, 2),
                    round(($budget->amount_executed / $budget->amount_allocated) * 100, 1) . '%',
                ])->toArray()
            );

            // Send notifications if requested
            if ($notify) {
                $this->info('Sending budget threshold alerts...');

                foreach ($exceeding as $budget) {
                    $percentageUsed = ($budget->amount_executed / $budget->amount_allocated) * 100;

                    event(new BudgetThresholdExceededEvent(
                        $budget,
                        $percentageUsed,
                        "{$threshold}%"
                    ));
                }

                $this->info("✓ Alerts sent for {$exceeding->count()} budgets");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to check budget thresholds: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
