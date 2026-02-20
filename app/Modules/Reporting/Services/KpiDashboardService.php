<?php

namespace App\Modules\Reporting\Services;

use App\Models\Requisition;
use App\Modules\PurchaseOrders\Models\PurchaseOrder;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\SupplierInvoice;
use App\Models\ProcurementProcess;
use App\Modules\Planning\Models\AnnualProcurementPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * KPI Dashboard Service
 * 
 * Calculates and aggregates key performance indicators for procurement reporting
 */
class KpiDashboardService
{
    /**
     * Get comprehensive KPIs for specified period
     */
    public function getKpiDashboard(Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        $cacheKey = "kpi_dashboard_" . $startDate->format('Y-m-d') . "_" . $endDate->format('Y-m-d');
        $cacheDuration = 3600; // 1 hour

        return Cache::remember($cacheKey, $cacheDuration, function () use ($startDate, $endDate, $filters) {
            return [
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'days' => $startDate->diffInDays($endDate),
                ],
                'procurement_cycle_time' => $this->getProcurementCycleTimeKpis($startDate, $endDate),
                'budget_utilization' => $this->getBudgetUtilizationKpis($startDate, $endDate),
                'supplier_performance' => $this->getSupplierPerformanceKpis($startDate, $endDate),
                'compliance' => $this->getComplianceKpis($startDate, $endDate),
                'cost_savings' => $this->getCostSavingsKpis($startDate, $endDate),
                'payment_efficiency' => $this->getPaymentEfficiencyKpis($startDate, $endDate),
                'process_efficiency' => $this->getProcessEfficiencyKpis($startDate, $endDate),
                'variance_analysis' => $this->getVarianceAnalysisKpis($startDate, $endDate),
            ];
        });
    }

    /**
     * Procurement Cycle Time KPIs
     */
    protected function getProcurementCycleTimeKpis(Carbon $startDate, Carbon $endDate): array
    {
        $completedRequisitions = Requisition::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('completed_at')
            ->get();

        $cycleTimes = $completedRequisitions->map(function ($req) {
            return Carbon::parse($req->created_at)->diffInDays(Carbon::parse($req->completed_at));
        });

        return [
            'average_cycle_days' => $cycleTimes->avg() ?? 0,
            'median_cycle_days' => $cycleTimes->median() ?? 0,
            'min_cycle_days' => $cycleTimes->min() ?? 0,
            'max_cycle_days' => $cycleTimes->max() ?? 0,
            'total_requisitions' => $completedRequisitions->count(),
            'requisitions_under_7_days' => $cycleTimes->filter(fn($d) => $d <= 7)->count(),
            'requisitions_7_to_14_days' => $cycleTimes->filter(fn($d) => $d > 7 && $d <= 14)->count(),
            'requisitions_over_14_days' => $cycleTimes->filter(fn($d) => $d > 14)->count(),
        ];
    }

    /**
     * Budget Utilization KPIs
     */
    protected function getBudgetUtilizationKpis(Carbon $startDate, Carbon $endDate): array
    {
        $payments = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->get();

        $totalBudget = config('procurement.annual_budget', 10000000); // Should come from budget module
        $totalSpent = $payments->sum('net_amount_base');
        $utilizationRate = $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0;

        // Get budget by department
        $spendByDepartment = Requisition::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'po_created'])
            ->join('departments', 'requisitions.department_id', '=', 'departments.id')
            ->groupBy('departments.id', 'departments.name')
            ->select('departments.name', DB::raw('SUM(requisitions.estimated_cost) as total_spend'))
            ->get();

        return [
            'total_budget_kes' => $totalBudget,
            'total_spent_kes' => round($totalSpent, 2),
            'available_budget_kes' => round($totalBudget - $totalSpent, 2),
            'utilization_percentage' => round($utilizationRate, 2),
            'spend_by_department' => $spendByDepartment->map(fn($d) => [
                'department' => $d->name,
                'total_spend' => round($d->total_spend, 2),
            ])->toArray(),
        ];
    }

    /**
     * Supplier Performance KPIs
     */
    protected function getSupplierPerformanceKpis(Carbon $startDate, Carbon $endDate): array
    {
        $pos = PurchaseOrder::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'closed'])
            ->with('supplier')
            ->get();

        $onTimeDeliveries = $pos->filter(function ($po) {
            if (!$po->delivery_date || !$po->expected_delivery_date) {
                return false;
            }
            return Carbon::parse($po->delivery_date)->lte(Carbon::parse($po->expected_delivery_date));
        })->count();

        $lateDeliveries = $pos->filter(function ($po) {
            if (!$po->delivery_date || !$po->expected_delivery_date) {
                return false;
            }
            return Carbon::parse($po->delivery_date)->gt(Carbon::parse($po->expected_delivery_date));
        })->count();

        $onTimeRate = ($onTimeDeliveries + $lateDeliveries) > 0
            ? ($onTimeDeliveries / ($onTimeDeliveries + $lateDeliveries)) * 100
            : 0;

        return [
            'total_suppliers_engaged' => $pos->pluck('supplier_id')->unique()->count(),
            'total_orders' => $pos->count(),
            'on_time_deliveries' => $onTimeDeliveries,
            'late_deliveries' => $lateDeliveries,
            'on_time_delivery_rate' => round($onTimeRate, 2),
            'average_delivery_days' => $pos->avg('delivery_days') ?? 0,
        ];
    }

    /**
     * Compliance KPIs
     */
    protected function getComplianceKpis(Carbon $startDate, Carbon $endDate): array
    {
        $totalPOs = PurchaseOrder::whereBetween('created_at', [$startDate, $endDate])->count();
        
        // 3-way match compliance
        $invoicesWithMatch = SupplierInvoice::whereBetween('created_at', [$startDate, $endDate])
            ->where('three_way_match_passed', true)
            ->count();
        
        $totalInvoices = SupplierInvoice::whereBetween('created_at', [$startDate, $endDate])->count();
        
        $matchComplianceRate = $totalInvoices > 0 ? ($invoicesWithMatch / $totalInvoices) * 100 : 0;

        // eTIMS compliance
        $invoicesWithEtims = SupplierInvoice::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('etims_control_number')
            ->count();
        
        $etimsComplianceRate = $totalInvoices > 0 ? ($invoicesWithEtims / $totalInvoices) * 100 : 0;

        // Threshold compliance (proper approvals)
        $requisitionsWithProperApproval = Requisition::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('approved_by')
            ->count();
        
        $totalRequisitions = Requisition::whereBetween('created_at', [$startDate, $endDate])->count();
        
        $approvalComplianceRate = $totalRequisitions > 0 ? ($requisitionsWithProperApproval / $totalRequisitions) * 100 : 0;

        return [
            'three_way_match_compliance_rate' => round($matchComplianceRate, 2),
            'etims_compliance_rate' => round($etimsComplianceRate, 2),
            'approval_compliance_rate' => round($approvalComplianceRate, 2),
            'total_invoices_processed' => $totalInvoices,
            'invoices_with_match' => $invoicesWithMatch,
            'invoices_with_etims' => $invoicesWithEtims,
            'overall_compliance_score' => round(($matchComplianceRate + $etimsComplianceRate + $approvalComplianceRate) / 3, 2),
        ];
    }

    /**
     * Cost Savings KPIs
     */
    protected function getCostSavingsKpis(Carbon $startDate, Carbon $endDate): array
    {
        $processes = ProcurementProcess::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['awarded', 'completed'])
            ->get();

        $totalEstimated = $processes->sum('budget_allocation');
        $totalActual = $processes->sum(function ($process) {
            return $process->awarded_bid_id
                ? optional($process->bids()->find($process->awarded_bid_id))->bid_amount ?? 0
                : 0;
        });

        $savings = $totalEstimated - $totalActual;
        $savingsRate = $totalEstimated > 0 ? ($savings / $totalEstimated) * 100 : 0;

        return [
            'total_estimated_cost_kes' => round($totalEstimated, 2),
            'total_actual_cost_kes' => round($totalActual, 2),
            'total_savings_kes' => round($savings, 2),
            'savings_percentage' => round($savingsRate, 2),
            'procurement_processes_completed' => $processes->count(),
        ];
    }

    /**
     * Payment Efficiency KPIs
     */
    protected function getPaymentEfficiencyKpis(Carbon $startDate, Carbon $endDate): array
    {
        $payments = Payment::whereBetween('created_at', [$startDate, $endDate])->get();
        
        $completedPayments = $payments->where('status', 'completed');
        $averageProcessingDays = $completedPayments->map(function ($payment) {
            if (!$payment->created_at || !$payment->processed_at) {
                return null;
            }
            return Carbon::parse($payment->created_at)->diffInDays(Carbon::parse($payment->processed_at));
        })->filter()->avg() ?? 0;

        $paymentsUnder7Days = $completedPayments->filter(function ($payment) {
            if (!$payment->created_at || !$payment->processed_at) {
                return false;
            }
            return Carbon::parse($payment->created_at)->diffInDays(Carbon::parse($payment->processed_at)) <= 7;
        })->count();

        $onTimePaymentRate = $completedPayments->count() > 0
            ? ($paymentsUnder7Days / $completedPayments->count()) * 100
            : 0;

        return [
            'total_payments' => $payments->count(),
            'completed_payments' => $completedPayments->count(),
            'pending_payments' => $payments->whereIn('status', ['draft', 'pending_verification', 'pending_approval'])->count(),
            'average_processing_days' => round($averageProcessingDays, 2),
            'payments_processed_under_7_days' => $paymentsUnder7Days,
            'on_time_payment_rate' => round($onTimePaymentRate, 2),
            'total_payment_value_kes' => round($completedPayments->sum('net_amount_base'), 2),
        ];
    }

    /**
     * Process Efficiency KPIs
     */
    protected function getProcessEfficiencyKpis(Carbon $startDate, Carbon $endDate): array
    {
        $requisitions = Requisition::whereBetween('created_at', [$startDate, $endDate])->get();
        
        return [
            'total_requisitions' => $requisitions->count(),
            'approved_requisitions' => $requisitions->whereIn('status', ['hod_approved', 'budget_approved', 'procurement_queue', 'completed'])->count(),
            'rejected_requisitions' => $requisitions->where('status', 'rejected')->count(),
            'cancelled_requisitions' => $requisitions->where('status', 'cancelled')->count(),
            'approval_rate' => $requisitions->count() > 0
                ? ($requisitions->whereIn('status', ['hod_approved', 'budget_approved', 'procurement_queue', 'completed'])->count() / $requisitions->count()) * 100
                : 0,
            'emergency_procurements' => $requisitions->where('is_emergency', true)->count(),
            'single_source_procurements' => ProcurementProcess::whereBetween('created_at', [$startDate, $endDate])
                ->where('procurement_method', 'single_source')
                ->count(),
        ];
    }

    /**
     * Variance Analysis KPIs
     */
    protected function getVarianceAnalysisKpis(Carbon $startDate, Carbon $endDate): array
    {
        $invoices = SupplierInvoice::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('three_way_match_details')
            ->get();

        $invoicesWithVariance = $invoices->filter(function ($invoice) {
            $details = $invoice->three_way_match_details;
            return isset($details['has_variance']) && $details['has_variance'];
        });

        $totalVariance = $invoicesWithVariance->sum(function ($invoice) {
            return $invoice->three_way_match_details['total_variance'] ?? 0;
        });

        return [
            'total_invoices_checked' => $invoices->count(),
            'invoices_with_variance' => $invoicesWithVariance->count(),
            'invoices_without_variance' => $invoices->count() - $invoicesWithVariance->count(),
            'variance_rate' => $invoices->count() > 0
                ? ($invoicesWithVariance->count() / $invoices->count()) * 100
                : 0,
            'total_variance_amount_kes' => round($totalVariance, 2),
            'average_variance_kes' => $invoicesWithVariance->count() > 0
                ? round($totalVariance / $invoicesWithVariance->count(), 2)
                : 0,
        ];
    }

    /**
     * Get fiscal year KPIs
     */
    public function getFiscalYearKpis(string $fiscalYear): array
    {
        // Parse fiscal year (e.g., "2025/2026" means July 2025 to June 2026)
        list($startYear, $endYear) = explode('/', $fiscalYear);
        $startDate = Carbon::create($startYear, 7, 1)->startOfDay();
        $endDate = Carbon::create($endYear, 6, 30)->endOfDay();

        return $this->getKpiDashboard($startDate, $endDate);
    }

    /**
     * Get quarterly KPIs
     */
    public function getQuarterlyKpis(string $fiscalYear, string $quarter): array
    {
        list($startYear, $endYear) = explode('/', $fiscalYear);
        
        $quarterDates = [
            'Q1' => [Carbon::create($startYear, 7, 1), Carbon::create($startYear, 9, 30)],
            'Q2' => [Carbon::create($startYear, 10, 1), Carbon::create($startYear, 12, 31)],
            'Q3' => [Carbon::create($endYear, 1, 1), Carbon::create($endYear, 3, 31)],
            'Q4' => [Carbon::create($endYear, 4, 1), Carbon::create($endYear, 6, 30)],
        ];

        if (!isset($quarterDates[$quarter])) {
            throw new \Exception("Invalid quarter: {$quarter}");
        }

        list($startDate, $endDate) = $quarterDates[$quarter];
        return $this->getKpiDashboard($startDate, $endDate);
    }

    /**
     * Clear KPI cache
     */
    public function clearCache(): void
    {
        Cache::flush();
    }
}
