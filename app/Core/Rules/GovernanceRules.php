<?php

namespace App\Core\Rules;

use App\Core\Audit\AuditService;
use App\Models\BudgetLine;
use Exception;

/**
 * Governance Rules Engine
 * 
 * Purpose: Enforce segregation of duties, three-way match, approval thresholds
 * This is the core control layer for compliance
 */
class GovernanceRules
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Enforce Segregation of Duties
     * Ensures the same user cannot perform adjacent workflow steps
     */
    public function enforceSegregationOfDuties(
        int $userId,
        string $action,
        $model,
        array $forbiddenActions = []
    ): bool {
        if (!config('procurement.governance.segregation_of_duties.enforce', true)) {
            return true;
        }

        // Check if user has performed any forbidden actions on this model
        $conflicts = $this->auditService->getAuditTrail(get_class($model), $model->id, 1000);

        foreach ($conflicts as $log) {
            if ($log->user_id === $userId && in_array($log->action, $forbiddenActions)) {
                if (!config('procurement.governance.segregation_of_duties.allow_override', false)) {
                    throw new Exception(
                        "Segregation of duties violation: User cannot perform '{$action}' " .
                            "after performing '{$log->action}' on the same record"
                    );
                }

                // Log the violation even if override is allowed
                $this->auditService->logPolicyViolation(
                    'segregation_of_duties',
                    get_class($model),
                    $model->id,
                    "User {$userId} performed {$action} after {$log->action}",
                    ['override_allowed' => true]
                );

                return false; // Violation but allowed
            }
        }

        return true; // No violation
    }

    /**
     * Validate Requester is not Approver
     */
    public function validateRequesterNotApprover(int $requesterId, int $approverId): void
    {
        if ($requesterId === $approverId) {
            throw new Exception("Governance violation: Requester cannot approve their own request");
        }
    }

    /**
     * Validate Approver is not Buyer
     */
    public function validateApproverNotBuyer(int $approverId, int $buyerId): void
    {
        if ($approverId === $buyerId) {
            throw new Exception("Governance violation: Approver cannot be the buyer for items they approved");
        }
    }

    /**
     * Validate Buyer is not Receiver
     */
    public function validateBuyerNotReceiver(int $buyerId, int $receiverId): void
    {
        if ($buyerId === $receiverId) {
            throw new Exception("Governance violation: Buyer cannot receive goods they purchased");
        }
    }

    /**
     * Validate Three-Way Match
     * PO + GRN + Invoice must match within tolerance
     */
    public function validateThreeWayMatch(array $po, array $grn, array $invoice): array
    {
        if (!config('procurement.governance.three_way_match.enforce', true)) {
            return ['matched' => true, 'variances' => []];
        }

        $tolerance = config('procurement.governance.three_way_match.tolerance_percentage', 2);
        $variances = [];

        // Compare quantities
        if ($grn['quantity'] !== $po['quantity']) {
            $variance = abs($grn['quantity'] - $po['quantity']);
            $variancePercent = ($variance / $po['quantity']) * 100;

            if ($variancePercent > $tolerance) {
                $variances[] = [
                    'field' => 'quantity',
                    'po_value' => $po['quantity'],
                    'grn_value' => $grn['quantity'],
                    'invoice_value' => $invoice['quantity'] ?? null,
                    'variance' => $variance,
                    'variance_percent' => round($variancePercent, 2),
                ];
            }
        }

        // Compare amounts
        $poAmount = $po['amount'];
        $invoiceAmount = $invoice['amount'];

        $amountVariance = abs($invoiceAmount - $poAmount);
        $amountVariancePercent = ($amountVariance / $poAmount) * 100;

        if ($amountVariancePercent > $tolerance) {
            $variances[] = [
                'field' => 'amount',
                'po_value' => $poAmount,
                'grn_value' => $grn['amount'] ?? null,
                'invoice_value' => $invoiceAmount,
                'variance' => $amountVariance,
                'variance_percent' => round($amountVariancePercent, 2),
            ];
        }

        $matched = empty($variances);

        return [
            'matched' => $matched,
            'variances' => $variances,
            'tolerance_percent' => $tolerance,
        ];
    }

    // -------------------------------------------------------------------------
    // Cash Band System (5-tier, PPADA-aligned)
    // -------------------------------------------------------------------------

    /**
     * Determine the cash band for a given amount.
     * Returns the full band config array (label, min, max, method, min_quotes, approvers).
     */
    public function determineCashBand(float $amount): array
    {
        $bands = config('procurement.cash_bands', []);

        foreach ($bands as $key => $band) {
            if ($band['max'] === null || $amount <= $band['max']) {
                return array_merge(['key' => $key], $band);
            }
        }

        // Fallback to strategic if nothing matched
        $strategic = $bands['strategic'];
        return array_merge(['key' => 'strategic'], $strategic);
    }

    /**
     * Return the required sourcing method for the given amount.
     * Values: spot_buy | rfq | rfq_formal | tender
     */
    public function getRequiredSourcingMethod(float $amount): string
    {
        return $this->determineCashBand($amount)['method'];
    }

    /**
     * Return the minimum number of quotes/bids required for the given amount.
     */
    public function getMinimumQuotes(float $amount): int
    {
        return (int) $this->determineCashBand($amount)['min_quotes'];
    }

    /**
     * Return the required approver roles for the given amount.
     */
    public function getRequiredApprovers(float $amount): array
    {
        return $this->determineCashBand($amount)['approvers'];
    }

    /**
     * Determine required approval level based on amount
     */
    public function getRequiredApprovalLevel(float $amount): array
    {
        $thresholds = config('procurement.thresholds', []);

        $levels = [];

        // All requisitions must be approved by the Department Head
        $levels[] = 'hod';

        // Need Principal approval
        if ($amount >= $thresholds['principal_approval']) {
            $levels[] = 'principal';
        }

        // Need Board approval
        if ($amount >= $thresholds['board_approval']) {
            $levels[] = 'board';
        }

        return $levels;
    }

    /**
     * Check if tender process is required
     */
    public function requiresTender(float $amount): bool
    {
        $threshold = config('procurement.thresholds.tender_required', 500000);
        return $amount >= $threshold;
    }

    /**
     * Check if multiple quotations required
     */
    public function requiresMultipleQuotations(float $amount): int
    {
        $threshold = config('procurement.thresholds.single_source_threshold', 50000);
        $minQuotations = config('procurement.thresholds.quotations_required', 3);

        return $amount >= $threshold ? $minQuotations : 1;
    }

    /**
     * Validate budget availability against the real BudgetLine table.
     *
     * Returns:
     *   'available'        — false only when no matching BudgetLine exists
     *   'sufficient'       — whether available_balance >= requested amount
     *   'available_balance'— allocated − committed − spent
     */
    public function validateBudgetAvailability(string $budgetCode, float $amount, string $fiscalYear): array
    {
        $budgetLine = BudgetLine::where('budget_code', $budgetCode)
            ->where('fiscal_year', $fiscalYear)
            ->where('is_active', true)
            ->first();

        if (!$budgetLine) {
            return [
                'available'        => false,
                'budget_code'      => $budgetCode,
                'fiscal_year'      => $fiscalYear,
                'allocated'        => 0,
                'committed'        => 0,
                'spent'            => 0,
                'available_balance'=> 0,
                'requested_amount' => $amount,
                'sufficient'       => false,
                'error'            => "No active budget line found for code '{$budgetCode}' in fiscal year '{$fiscalYear}'",
            ];
        }

        $allocated        = (float) $budgetLine->allocated_amount;
        $committed        = (float) $budgetLine->committed_amount;
        $spent            = (float) $budgetLine->spent_amount;
        $availableBalance = $allocated - $committed - $spent;

        $overrunAllowed = config('procurement.budget.overrun_allowed', false);
        $sufficient     = $overrunAllowed
            ? true
            : $availableBalance >= $amount;

        return [
            'available'        => true,
            'budget_code'      => $budgetCode,
            'fiscal_year'      => $fiscalYear,
            'allocated'        => $allocated,
            'committed'        => $committed,
            'spent'            => $spent,
            'available_balance'=> $availableBalance,
            'requested_amount' => $amount,
            'sufficient'       => $sufficient,
        ];
    }

    /**
     * Validate single-source justification
     */
    public function validateSingleSource(float $amount, ?string $justification): void
    {
        if (!config('procurement.single_source.allowed', true)) {
            throw new Exception("Single-source procurement is not allowed by policy");
        }

        $threshold = config('procurement.thresholds.single_source_threshold', 50000);

        if ($amount >= $threshold) {
            if (config('procurement.single_source.requires_justification', true) && empty($justification)) {
                throw new Exception("Single-source procurement above KES {$threshold} requires justification");
            }
        }
    }

    /**
     * Validate emergency procurement
     */
    public function validateEmergencyProcurement(float $amount, string $justification): void
    {
        if (!config('procurement.emergency.enabled', true)) {
            throw new Exception("Emergency procurement is not enabled");
        }

        $maxAmount = config('procurement.emergency.max_amount', 100000);

        if ($amount > $maxAmount) {
            throw new Exception("Emergency procurement limited to KES {$maxAmount}");
        }

        if (empty($justification)) {
            throw new Exception("Emergency procurement requires justification");
        }
    }

    /**
     * Check for supplier blacklist
     */
    public function isSupplierBlacklisted(int $supplierId): bool
    {
        // Would query supplier blacklist table
        // For now, returning false
        return false;
    }

    /**
     * Validate supplier compliance
     */
    public function validateSupplierCompliance(array $supplier): array
    {
        $issues = [];

        if (config('procurement.suppliers.require_kra_pin', true) && empty($supplier['kra_pin'])) {
            $issues[] = 'Missing KRA PIN';
        }

        if (config('procurement.suppliers.require_tax_compliance_cert', true)) {
            if (empty($supplier['tax_compliance_cert_expiry'])) {
                $issues[] = 'Missing Tax Compliance Certificate';
            } elseif (strtotime($supplier['tax_compliance_cert_expiry']) < time()) {
                $issues[] = 'Tax Compliance Certificate expired';
            }
        }

        if ($this->isSupplierBlacklisted($supplier['id'])) {
            $issues[] = 'Supplier is blacklisted';
        }

        return [
            'compliant' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Validate backdating
     */
    public function validateBackdating($date): void
    {
        if (!config('procurement.system.allow_backdating', false)) {
            if (strtotime($date) < strtotime('today')) {
                throw new Exception("Backdating is not allowed");
            }
        } else {
            $maxBackdateDays = config('procurement.system.max_backdate_days', 7);
            $diff = (strtotime('today') - strtotime($date)) / 86400;

            if ($diff > $maxBackdateDays) {
                throw new Exception("Cannot backdate more than {$maxBackdateDays} days");
            }
        }
    }
}
