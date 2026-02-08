<?php

namespace App\Core\TaxEngine;

use Exception;

/**
 * Kenya Tax Calculation Engine
 * 
 * Purpose: Handle VAT and WHT calculations according to Kenya tax regulations
 * Supports KRA compliance requirements
 */
class TaxEngine
{
    /**
     * Calculate VAT amount
     */
    public function calculateVAT(float $amount, ?float $rate = null, string $vatType = 'vatable'): array
    {
        if ($vatType === 'exempt' || $vatType === 'zero-rated') {
            return [
                'base_amount' => $amount,
                'vat_rate' => 0,
                'vat_amount' => 0,
                'total_amount' => $amount,
                'vat_type' => $vatType,
            ];
        }

        $vatRate = $rate ?? config('procurement.tax.vat.default_rate', 16);
        $vatAmount = round($amount * ($vatRate / 100), 2);
        $totalAmount = $amount + $vatAmount;

        return [
            'base_amount' => $amount,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'total_amount' => $totalAmount,
            'vat_type' => $vatType,
        ];
    }

    /**
     * Calculate VAT-exclusive amount from VAT-inclusive amount
     */
    public function extractVAT(float $inclusiveAmount, ?float $rate = null): array
    {
        $vatRate = $rate ?? config('procurement.tax.vat.default_rate', 16);
        $baseAmount = round($inclusiveAmount / (1 + ($vatRate / 100)), 2);
        $vatAmount = round($inclusiveAmount - $baseAmount, 2);

        return [
            'base_amount' => $baseAmount,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'total_amount' => $inclusiveAmount,
        ];
    }

    /**
     * Calculate WHT (Withholding Tax) deduction
     * 
     * @param float $grossAmount
     * @param string $whtType Type of WHT (services, professional_fees, etc.)
     * @param float|null $rate Optional override rate
     * @return array
     */
    public function calculateWHT(float $grossAmount, string $whtType = 'services', ?float $rate = null): array
    {
        $whtRates = config('procurement.tax.wht.rates', []);

        $whtRate = $rate ?? $whtRates[$whtType] ?? config('procurement.tax.wht.default_rate', 5);

        $whtAmount = round($grossAmount * ($whtRate / 100), 2);
        $netAmount = $grossAmount - $whtAmount;

        return [
            'gross_amount' => $grossAmount,
            'wht_type' => $whtType,
            'wht_rate' => $whtRate,
            'wht_amount' => $whtAmount,
            'net_amount' => $netAmount,
        ];
    }

    /**
     * Calculate comprehensive tax breakdown (VAT + WHT)
     * 
     * @param float $baseAmount
     * @param bool $includeVAT
     * @param string $vatType
     * @param bool $includeWHT
     * @param string $whtType
     * @return array
     */
    public function calculateComprehensive(
        float $baseAmount,
        bool $includeVAT = true,
        string $vatType = 'vatable',
        bool $includeWHT = false,
        string $whtType = 'services'
    ): array {
        // Calculate VAT
        $vatCalculation = $includeVAT
            ? $this->calculateVAT($baseAmount, null, $vatType)
            : [
                'base_amount' => $baseAmount,
                'vat_rate' => 0,
                'vat_amount' => 0,
                'total_amount' => $baseAmount,
                'vat_type' => 'not_applicable',
            ];

        $grossAmount = $vatCalculation['total_amount'];

        // Calculate WHT
        $whtCalculation = $includeWHT
            ? $this->calculateWHT($grossAmount, $whtType)
            : [
                'gross_amount' => $grossAmount,
                'wht_type' => 'not_applicable',
                'wht_rate' => 0,
                'wht_amount' => 0,
                'net_amount' => $grossAmount,
            ];

        return [
            'base_amount' => $baseAmount,
            'vat' => [
                'applicable' => $includeVAT,
                'type' => $vatCalculation['vat_type'],
                'rate' => $vatCalculation['vat_rate'],
                'amount' => $vatCalculation['vat_amount'],
            ],
            'gross_amount' => $grossAmount,
            'wht' => [
                'applicable' => $includeWHT,
                'type' => $whtCalculation['wht_type'],
                'rate' => $whtCalculation['wht_rate'],
                'amount' => $whtCalculation['wht_amount'],
            ],
            'net_payable' => $whtCalculation['net_amount'],
        ];
    }

    /**
     * Validate KRA PIN format
     */
    public function validateKRAPin(string $pin): bool
    {
        // KRA PIN format: A followed by 9 digits and ending with letter (e.g., A001234567Z)
        return preg_match('/^[A-Z]\d{9}[A-Z]$/', $pin) === 1;
    }

    /**
     * Get available WHT types
     */
    public function getWHTTypes(): array
    {
        $rates = config('procurement.tax.wht.rates', []);

        return array_map(function ($type, $rate) {
            return [
                'type' => $type,
                'rate' => $rate,
                'label' => ucwords(str_replace('_', ' ', $type)),
            ];
        }, array_keys($rates), $rates);
    }

    /**
     * Get VAT types
     */
    public function getVATTypes(): array
    {
        return [
            ['type' => 'vatable', 'label' => 'VATable', 'rate' => config('procurement.tax.vat.default_rate', 16)],
            ['type' => 'exempt', 'label' => 'VAT Exempt', 'rate' => 0],
            ['type' => 'zero-rated', 'label' => 'Zero-Rated', 'rate' => 0],
        ];
    }

    /**
     * Calculate tax for multiple line items
     */
    public function calculateLineItemsTax(array $lineItems): array
    {
        $totalBase = 0;
        $totalVAT = 0;
        $totalGross = 0;
        $totalWHT = 0;
        $totalNet = 0;

        $processedItems = [];

        foreach ($lineItems as $item) {
            $calculation = $this->calculateComprehensive(
                $item['amount'],
                $item['include_vat'] ?? true,
                $item['vat_type'] ?? 'vatable',
                $item['include_wht'] ?? false,
                $item['wht_type'] ?? 'services'
            );

            $totalBase += $calculation['base_amount'];
            $totalVAT += $calculation['vat']['amount'];
            $totalGross += $calculation['gross_amount'];
            $totalWHT += $calculation['wht']['amount'];
            $totalNet += $calculation['net_payable'];

            $processedItems[] = array_merge($item, $calculation);
        }

        return [
            'line_items' => $processedItems,
            'summary' => [
                'total_base' => round($totalBase, 2),
                'total_vat' => round($totalVAT, 2),
                'total_gross' => round($totalGross, 2),
                'total_wht' => round($totalWHT, 2),
                'total_net_payable' => round($totalNet, 2),
            ],
        ];
    }

    /**
     * Format KRA PIN for display
     */
    public function formatKRAPin(string $pin): string
    {
        if (strlen($pin) === 11) {
            return substr($pin, 0, 1) . ' ' .
                substr($pin, 1, 3) . ' ' .
                substr($pin, 4, 3) . ' ' .
                substr($pin, 7, 3) . ' ' .
                substr($pin, 10, 1);
        }

        return $pin;
    }
}
