<?php

namespace App\Core\CurrencyEngine;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * Multi-Currency Engine with KES Base
 * 
 * Purpose: Handle currency conversions, exchange rate management
 * Base Currency: KES (Kenyan Shilling)
 * Supported: USD, GBP, EUR
 */
class CurrencyEngine
{
    protected string $baseCurrency;
    protected array $supportedCurrencies;

    public function __construct()
    {
        $this->baseCurrency = config('procurement.currency.default', 'KES');
        $this->supportedCurrencies = config('procurement.currency.supported', ['KES', 'USD', 'GBP', 'EUR']);
    }

    /**
     * Convert amount from one currency to another
     */
    public function convert(float $amount, string $from, string $to, ?Carbon $date = null): float
    {
        if ($from === $to) {
            return $amount;
        }

        $this->validateCurrency($from);
        $this->validateCurrency($to);

        $rate = $this->getExchangeRate($from, $to, $date);

        return round($amount * $rate, 2);
    }

    /**
     * Convert to base currency (KES)
     */
    public function toBase(float $amount, string $from, ?Carbon $date = null): float
    {
        return $this->convert($amount, $from, $this->baseCurrency, $date);
    }

    /**
     * Convert from base currency (KES)
     */
    public function fromBase(float $amount, string $to, ?Carbon $date = null): float
    {
        return $this->convert($amount, $this->baseCurrency, $to, $date);
    }

    /**
     * Get exchange rate between two currencies
     */
    public function getExchangeRate(string $from, string $to, ?Carbon $date = null): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $date = $date ?? Carbon::now();
        $cacheKey = "exchange_rate_{$from}_{$to}_{$date->format('Y-m-d')}";
        $cacheDuration = config('procurement.currency.cache_exchange_rates_minutes', 1440);

        return Cache::remember($cacheKey, $cacheDuration * 60, function () use ($from, $to, $date) {
            return $this->fetchExchangeRate($from, $to, $date);
        });
    }

    /**
     * Fetch exchange rate from database
     */
    protected function fetchExchangeRate(string $from, string $to, Carbon $date): float
    {
        // Try to get rate for specific date
        $rate = DB::table('exchange_rates')
            ->where('from_currency', $from)
            ->where('to_currency', $to)
            ->whereDate('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();

        if (!$rate) {
            // Try reverse rate
            $reverseRate = DB::table('exchange_rates')
                ->where('from_currency', $to)
                ->where('to_currency', $from)
                ->whereDate('effective_date', '<=', $date)
                ->orderBy('effective_date', 'desc')
                ->first();

            if ($reverseRate) {
                return round(1 / $reverseRate->rate, 6);
            }

            // Try cross-rate through base currency
            if ($from !== $this->baseCurrency && $to !== $this->baseCurrency) {
                $fromToBase = $this->fetchExchangeRate($from, $this->baseCurrency, $date);
                $baseToTo = $this->fetchExchangeRate($this->baseCurrency, $to, $date);
                return round($fromToBase * $baseToTo, 6);
            }

            throw new Exception("Exchange rate not found for {$from} to {$to} on {$date->format('Y-m-d')}");
        }

        return (float) $rate->rate;
    }

    /**
     * Store exchange rate
     */
    public function storeExchangeRate(string $from, string $to, float $rate, ?Carbon $effectiveDate = null): int
    {
        $this->validateCurrency($from);
        $this->validateCurrency($to);

        if ($rate <= 0) {
            throw new Exception("Exchange rate must be greater than zero");
        }

        $effectiveDate = $effectiveDate ?? Carbon::now();

        return DB::table('exchange_rates')->insertGetId([
            'from_currency' => $from,
            'to_currency' => $to,
            'rate' => $rate,
            'effective_date' => $effectiveDate,
            'source' => 'manual',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Get current rates for all supported currencies to base
     */
    public function getCurrentRates(): array
    {
        $rates = [];
        foreach ($this->supportedCurrencies as $currency) {
            if ($currency === $this->baseCurrency) {
                $rates[$currency] = 1.0;
            } else {
                try {
                    $rates[$currency] = $this->getExchangeRate($currency, $this->baseCurrency);
                } catch (Exception $e) {
                    $rates[$currency] = null;
                }
            }
        }
        return $rates;
    }

    /**
     * Format amount with currency symbol
     */
    public function format(float $amount, string $currency): string
    {
        $formats = config('procurement.currency.display_format', []);
        $format = $formats[$currency] ?? "{$currency} %s";

        return sprintf($format, number_format($amount, 2));
    }

    /**
     * Format amount in base currency
     */
    public function formatBase(float $amount): string
    {
        return $this->format($amount, $this->baseCurrency);
    }

    /**
     * Validate currency code
     */
    protected function validateCurrency(string $currency): void
    {
        if (!in_array($currency, $this->supportedCurrencies)) {
            throw new Exception("Unsupported currency: {$currency}. Supported: " . implode(', ', $this->supportedCurrencies));
        }
    }

    /**
     * Check if currency is supported
     */
    public function isSupported(string $currency): bool
    {
        return in_array($currency, $this->supportedCurrencies);
    }

    /**
     * Get base currency
     */
    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    /**
     * Calculate FX gain/loss
     */
    public function calculateFXVariance(
        float $originalAmount,
        string $originalCurrency,
        float $originalRate,
        float $currentRate
    ): array {
        $originalBase = $originalAmount * $originalRate;
        $currentBase = $originalAmount * $currentRate;
        $variance = $currentBase - $originalBase;

        return [
            'original_amount' => $originalAmount,
            'currency' => $originalCurrency,
            'original_rate' => $originalRate,
            'current_rate' => $currentRate,
            'original_base_value' => round($originalBase, 2),
            'current_base_value' => round($currentBase, 2),
            'variance' => round($variance, 2),
            'variance_percentage' => $originalBase > 0 ? round(($variance / $originalBase) * 100, 2) : 0,
            'variance_type' => $variance >= 0 ? 'gain' : 'loss',
        ];
    }

    /**
     * Lock exchange rate for transaction
     */
    public function lockRate(string $transactionType, int $transactionId, string $from, string $to, float $rate): int
    {
        return DB::table('locked_exchange_rates')->insertGetId([
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId,
            'from_currency' => $from,
            'to_currency' => $to,
            'locked_rate' => $rate,
            'locked_at' => Carbon::now(),
            'created_at' => Carbon::now(),
        ]);
    }

    /**
     * Get locked rate for transaction
     */
    public function getLockedRate(string $transactionType, int $transactionId, string $from, string $to): ?float
    {
        $locked = DB::table('locked_exchange_rates')
            ->where('transaction_type', $transactionType)
            ->where('transaction_id', $transactionId)
            ->where('from_currency', $from)
            ->where('to_currency', $to)
            ->first();

        return $locked ? (float) $locked->locked_rate : null;
    }
}
