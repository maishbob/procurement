<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ExchangeRate;

class UpdateExchangeRatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tries = 3;
    protected int $backoff = 120;

    public function __construct(
        protected ?string $provider = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $provider = $this->provider ?? config('procurement.fx_provider', 'openexchangerates');

            // Fetch rates from provider
            $rates = match ($provider) {
                'openexchangerates' => $this->fetchFromOpenExchangeRates(),
                'fixer' => $this->fetchFromFixer(),
                'xe' => $this->fetchFromXE(),
                'cbk' => $this->fetchFromCentralBankOfKenya(),
                default => throw new \Exception("Unknown FX provider: {$provider}")
            };

            // Update exchange rates in database
            foreach ($rates as $currency => $rate) {
                ExchangeRate::updateOrCreate(
                    [
                        'currency' => strtoupper($currency),
                        'base_currency' => 'KES',
                    ],
                    [
                        'rate' => $rate,
                        'rate_date' => now()->date(),
                        'source' => $provider,
                        'updated_at' => now(),
                    ]
                );
            }

            // Log successful update
            app(\App\Core\Audit\AuditService::class)->log(
                'EXCHANGE_RATES_UPDATED',
                'ExchangeRate',
                null,
                null,
                null,
                'Exchange rates updated from ' . $provider,
                [
                    'provider' => $provider,
                    'rates_updated' => count($rates),
                    'currencies' => array_keys($rates),
                ]
            );
        } catch (\Exception $e) {
            app(\App\Core\Audit\AuditService::class)->log(
                'EXCHANGE_RATES_UPDATE_FAILED',
                'ExchangeRate',
                null,
                null,
                null,
                'Failed to update exchange rates: ' . $e->getMessage(),
                [
                    'provider' => $provider ?? 'unknown',
                    'error' => $e->getMessage(),
                ]
            );

            if ($this->attempts() < $this->tries) {
                $this->release(120); // Retry after 2 minutes
            } else {
                $this->fail($e);
            }
        }
    }

    /**
     * Fetch rates from Open Exchange Rates API
     */
    protected function fetchFromOpenExchangeRates(): array
    {
        $apiKey = config('services.openexchangerates.key');

        $response = \Http::get('https://openexchangerates.org/api/latest.json', [
            'app_id' => $apiKey,
            'base' => 'KES',
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch rates from Open Exchange Rates');
        }

        $data = $response->json();

        // Filter for major currencies
        $majors = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'SEK'];
        $rates = [];

        foreach ($majors as $currency) {
            if (isset($data['rates'][$currency])) {
                $rates[$currency] = $data['rates'][$currency];
            }
        }

        return $rates;
    }

    /**
     * Fetch rates from Fixer.io API
     */
    protected function fetchFromFixer(): array
    {
        $apiKey = config('services.fixer.key');

        $response = \Http::get('https://api.fixer.io/latest', [
            'access_key' => $apiKey,
            'base' => 'KES',
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch rates from Fixer');
        }

        return $response->json('rates', []);
    }

    /**
     * Fetch rates from XE.com API
     */
    protected function fetchFromXE(): array
    {
        $apiKey = config('services.xe.key');

        $response = \Http::withBasicAuth($apiKey, '')
            ->get('https://xecdapi.xe.com/v1/historic', [
                'start_timestamp' => now()->subDay()->timestamp,
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch rates from XE');
        }

        $rates = [];
        foreach ($response->json('quotes', []) as $pair => $quote) {
            if (str_starts_with($pair, 'KES')) {
                $currency = substr($pair, 3);
                $rates[$currency] = $quote['mid'] ?? 0;
            }
        }

        return $rates;
    }

    /**
     * Fetch rates from Central Bank of Kenya
     */
    protected function fetchFromCentralBankOfKenya(): array
    {
        // CBK publishes rates daily on their website
        $response = \Http::get('https://www.centralbank.go.ke/api/exchange-rates');

        if ($response->failed()) {
            throw new \Exception('Failed to fetch rates from CBK');
        }

        $rates = [];
        $data = $response->json();

        // Parse CBK response format
        foreach ($data['exchange_rates'] ?? [] as $rate) {
            $rates[$rate['currency']] = $rate['rate'];
        }

        return $rates;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'scheduled',
            'exchange-rates',
            'provider:' . ($this->provider ?? 'default'),
        ];
    }
}
