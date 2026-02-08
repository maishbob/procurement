<?php

namespace App\Console\Commands;

use App\Jobs\UpdateExchangeRatesJob;
use Illuminate\Console\Command;

class UpdateExchangeRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'procurement:update-exchange-rates
                            {--provider= : FX provider (openexchangerates, fixer, xe, cbk)}
                            {--async : Queue job asynchronously}';

    /**
     * The console command description.
     */
    protected $description = 'Update daily exchange rates from configured provider';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $provider = $this->option('provider') ?? config('procurement.fx_provider', 'openexchangerates');
        $async = $this->option('async');

        $this->info("Updating exchange rates from {$provider}...");

        try {
            if ($async) {
                dispatch(new UpdateExchangeRatesJob($provider));
                $this->info("✓ Exchange rate update queued (async)");
            } else {
                // Execute synchronously
                $job = new UpdateExchangeRatesJob($provider);
                $job->handle();
                $this->info("✓ Exchange rates updated successfully");
            }

            // Show current rates
            $rates = \DB::table('exchange_rates')
                ->where('base_currency', 'KES')
                ->where('rate_date', now()->date())
                ->get();

            if ($rates->isNotEmpty()) {
                $this->table(
                    ['Currency', 'Rate', 'Last Updated'],
                    $rates->map(fn($r) => [
                        $r->currency,
                        number_format($r->rate, 4),
                        $r->updated_at
                    ])->toArray()
                );
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to update exchange rates: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
