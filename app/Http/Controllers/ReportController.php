<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Modules\Reporting\Services\KpiDashboardService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
        private KpiDashboardService $kpiDashboardService
    ) {}

    /**
     * Display reports dashboard
     */
    public function index()
    {
        $this->authorize('viewAny', \App\Models\Requisition::class);

        return redirect()->route('reports.dashboard');
    }

    /**
     * KPI Dashboard — T-4.5
     */
    public function dashboard(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Requisition::class);

        // Default to current Kenya fiscal year (July–June)
        $now = now()->timezone(config('procurement.system.timezone', 'Africa/Nairobi'));
        $defaultFiscalYear = $now->month >= 7
            ? $now->year . '/' . ($now->year + 1)
            : ($now->year - 1) . '/' . $now->year;

        $fiscalYear = $request->get('fiscal_year', $defaultFiscalYear);

        // Determine current quarter within fiscal year
        $month = $now->month;
        $quarter = match (true) {
            $month >= 7 && $month <= 9   => 'Q1',
            $month >= 10 && $month <= 12 => 'Q2',
            $month >= 1 && $month <= 3   => 'Q3',
            default                      => 'Q4',
        };

        $data        = $this->kpiDashboardService->getFiscalYearKpis($fiscalYear);
        $quarterData = $this->kpiDashboardService->getQuarterlyKpis($fiscalYear, $quarter);

        return view('reports.dashboard', compact('data', 'quarterData', 'fiscalYear', 'quarter'));
    }

    public function show($id)
    {
        abort(404);
    }

    /**
     * Display requisitions report
     */
    public function requisitionsReport(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'status' => $request->get('status'),
            'department_id' => $request->get('department_id'),
        ];

        $report = $this->reportService->getRequisitionReport($filters);

        return view('reports.requisitions', compact('report', 'filters'));
    }

    /**
     * Export requisitions report
     */
    public function exportRequisitions(Request $request)
    {
        $format = $request->get('format', 'excel'); // excel, pdf, csv

        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'status' => $request->get('status'),
            'department_id' => $request->get('department_id'),
        ];

        try {
            return $this->reportService->exportRequisitionReport($filters, $format);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export: ' . $e->getMessage());
        }
    }

    /**
     * Display procurement report
     */
    public function procurementReport(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'process_type' => $request->get('process_type'), // RFQ, RFP, Tender
        ];

        $report = $this->reportService->getProcurementReport($filters);

        return view('reports.procurement', compact('report', 'filters'));
    }

    /**
     * Export procurement report
     */
    public function exportProcurement(Request $request)
    {
        $format = $request->get('format', 'excel');

        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'process_type' => $request->get('process_type'),
        ];

        try {
            return $this->reportService->exportProcurementReport($filters, $format);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export: ' . $e->getMessage());
        }
    }

    /**
     * Display suppliers report
     */
    public function suppliersReport(Request $request)
    {
        $filters = [
            'status' => $request->get('status'), // approved, blacklisted, pending
            'category_id' => $request->get('category_id'),
        ];

        $report = $this->reportService->getSupplierReport($filters);

        return view('reports.suppliers', compact('report', 'filters'));
    }

    /**
     * Display supplier performance report
     */
    public function supplierPerformance(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $report = $this->reportService->getSupplierPerformanceReport($filters);

        return view('reports.supplier-performance', compact('report', 'filters'));
    }

    /**
     * Export suppliers report
     */
    public function exportSuppliers(Request $request)
    {
        $format = $request->get('format', 'excel');

        $filters = [
            'status' => $request->get('status'),
            'category_id' => $request->get('category_id'),
        ];

        try {
            return $this->reportService->exportSupplierReport($filters, $format);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export: ' . $e->getMessage());
        }
    }

    /**
     * Display inventory report
     */
    public function inventoryReport(Request $request)
    {
        $filters = [
            'store_id' => $request->get('store_id'),
            'status' => $request->get('status'), // low_stock, out_of_stock, adequate
        ];

        $report = $this->reportService->getInventoryReport($filters);

        return view('reports.inventory', compact('report', 'filters'));
    }

    /**
     * Display inventory valuation report
     */
    public function inventoryValuation(Request $request)
    {
        $filters = [
            'store_id' => $request->get('store_id'),
            'valuation_date' => $request->get('valuation_date', now()->format('Y-m-d')),
        ];

        $report = $this->reportService->getInventoryValuationReport($filters);

        return view('reports.inventory-valuation', compact('report', 'filters'));
    }

    /**
     * Display inventory movements report
     */
    public function inventoryMovements(Request $request)
    {
        $filters = [
            'store_id' => $request->get('store_id'),
            'transaction_type' => $request->get('transaction_type'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $report = $this->reportService->getInventoryMovementsReport($filters);

        return view('reports.inventory-movements', compact('report', 'filters'));
    }

    /**
     * Export inventory report
     */
    public function exportInventory(Request $request)
    {
        $format = $request->get('format', 'excel');

        $filters = [
            'store_id' => $request->get('store_id'),
            'status' => $request->get('status'),
        ];

        try {
            return $this->reportService->exportInventoryReport($filters, $format);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export: ' . $e->getMessage());
        }
    }

    /**
     * Display financial report
     */
    public function financialReport(Request $request)
    {
        $filters = [
            'fiscal_year' => $request->get('fiscal_year', now()->year),
        ];

        $report = $this->reportService->getFinancialReport($filters);

        return view('reports.financial', compact('report', 'filters'));
    }

    /**
     * Display budget report
     */
    public function budgetReport(Request $request)
    {
        $filters = [
            'fiscal_year' => $request->get('fiscal_year', now()->year),
            'department_id' => $request->get('department_id'),
        ];

        $report = $this->reportService->getBudgetReport($filters);

        return view('reports.budget', compact('report', 'filters'));
    }

    /**
     * Display cash flow report
     */
    public function cashFlowReport(Request $request)
    {
        $filters = [
            'fiscal_year' => $request->get('fiscal_year', now()->year),
        ];

        $report = $this->reportService->getCashFlowReport($filters);

        return view('reports.cash-flow', compact('report', 'filters'));
    }

    /**
     * Display expenditure report
     */
    public function expenditureReport(Request $request)
    {
        $filters = [
            'fiscal_year' => $request->get('fiscal_year', now()->year),
            'department_id' => $request->get('department_id'),
        ];

        $report = $this->reportService->getExpenditureReport($filters);

        return view('reports.expenditure', compact('report', 'filters'));
    }

    /**
     * Display WithHolding Tax report
     */
    public function whtReport(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'fiscal_year' => $request->get('fiscal_year', now()->year),
        ];

        $report = $this->reportService->getWHTReport($filters);

        return view('reports.wht', compact('report', 'filters'));
    }

    /**
     * Export financial report
     */
    public function exportFinancial(Request $request)
    {
        $format = $request->get('format', 'excel');

        $filters = [
            'fiscal_year' => $request->get('fiscal_year', now()->year),
        ];

        try {
            return $this->reportService->exportFinancialReport($filters, $format);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export: ' . $e->getMessage());
        }
    }

    /**
     * Display performance dashboard
     */
    public function performanceDashboard(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $kpis = $this->reportService->getPerformanceKPIs($filters);

        return view('reports.performance', compact('kpis', 'filters'));
    }

    /**
     * Display compliance report
     */
    public function complianceReport(Request $request)
    {
        $filters = [
            'fiscal_year' => $request->get('fiscal_year', now()->year),
        ];

        $report = $this->reportService->getComplianceReport($filters);

        return view('reports.compliance', compact('report', 'filters'));
    }

    /**
     * Display scheduled reports management
     */
    public function scheduledReports()
    {
        $this->authorize('viewAny', \App\Models\Requisition::class);

        $scheduledReports = \App\Models\ScheduledReport::where('created_by', auth()->id())
            ->paginate(15);

        return view('reports.scheduled', compact('scheduledReports'));
    }

    /**
     * Create scheduled report
     */
    public function createScheduledReport(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:requisition,procurement,supplier,inventory,financial,budget',
            'schedule' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'recipient_emails' => 'required|array|min:1',
            'recipient_emails.*' => 'email',
            'export_format' => 'required|in:excel,pdf,csv',
        ]);

        try {
            \App\Models\ScheduledReport::create([
                'report_type' => $validated['report_type'],
                'schedule' => $validated['schedule'],
                'recipient_emails' => json_encode($validated['recipient_emails']),
                'export_format' => $validated['export_format'],
                'created_by' => auth()->id(),
            ]);

            return back()->with('success', 'Scheduled report created');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create: ' . $e->getMessage());
        }
    }

    /**
     * Delete scheduled report
     */
    public function deleteScheduledReport(\App\Models\ScheduledReport $scheduledReport)
    {
        if ($scheduledReport->created_by !== auth()->id()) {
            return back()->with('error', 'Unauthorized');
        }

        try {
            $scheduledReport->delete();

            return back()->with('success', 'Scheduled report deleted');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }
}
