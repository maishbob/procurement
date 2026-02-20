<?php

namespace App\Services;

use App\Models\Requisition;
use App\Models\BudgetLine;
use App\Modules\Finance\Models\SupplierInvoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\InventoryTransaction;
use App\Modules\Suppliers\Models\Supplier;
use App\Modules\PurchaseOrders\Models\PurchaseOrder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    // -------------------------------------------------------------------------
    // Requisition Reports
    // -------------------------------------------------------------------------

    public function getRequisitionReport(array $filters = []): array
    {
        return $this->generateRequisitionReport($filters);
    }

    public function generateRequisitionReport(array $filters = []): array
    {
        $query = Requisition::with('requester', 'department', 'approvals');

        if (!empty($filters['status']))        $query->where('status', $filters['status']);
        if (!empty($filters['department_id'])) $query->where('department_id', $filters['department_id']);
        if (!empty($filters['date_from']))     $query->whereDate('created_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))       $query->whereDate('created_at', '<=', $filters['date_to']);

        $requisitions = $query->get();

        return [
            'title'              => 'Requisition Status Report',
            'generated_at'       => now(),
            'total_requisitions' => $requisitions->count(),
            'by_status'          => $requisitions->groupBy('status')->map->count(),
            'total_value'        => $requisitions->sum(fn ($r) => $r->items->sum(fn ($i) => $i->quantity * $i->unit_price)),
            'pending_approval'   => $requisitions->whereIn('status', ['submitted', 'hod_review', 'budget_review'])->count(),
            'details'            => $requisitions->map(fn ($r) => [
                'requisition_id' => $r->id,
                'number'         => $r->requisition_number,
                'department'     => $r->department?->name ?? 'N/A',
                'requester'      => $r->requester?->name ?? 'N/A',
                'status'         => $r->status,
                'total_value'    => $r->items->sum(fn ($i) => $i->quantity * $i->unit_price),
                'items_count'    => $r->items->count(),
                'created_at'     => $r->created_at,
            ]),
        ];
    }

    public function exportRequisitionReport(array $filters = [], string $format = 'csv'): StreamedResponse
    {
        $report = $this->getRequisitionReport($filters);
        $headers = ['ID', 'Number', 'Department', 'Requester', 'Status', 'Total Value (KES)', 'Items', 'Date'];
        $data = $report['details']->map(fn ($r) => [
            $r['requisition_id'], $r['number'], $r['department'], $r['requester'],
            $r['status'], number_format($r['total_value'], 2), $r['items_count'], $r['created_at'],
        ])->toArray();

        return $this->streamCsv('requisitions_report', $headers, $data);
    }

    // -------------------------------------------------------------------------
    // Procurement (Purchase Order) Reports
    // -------------------------------------------------------------------------

    public function getProcurementReport(array $filters = []): array
    {
        return $this->generateProcurementReport($filters);
    }

    public function generateProcurementReport(array $filters = []): array
    {
        $query = PurchaseOrder::with('supplier', 'requisition');

        if (!empty($filters['process_type'])) $query->where('type', $filters['process_type']);
        if (!empty($filters['date_from']))    $query->whereDate('created_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))      $query->whereDate('created_at', '<=', $filters['date_to']);

        $orders = $query->get();

        return [
            'title'        => 'Procurement (Purchase Orders) Report',
            'generated_at' => now(),
            'total_pos'    => $orders->count(),
            'by_status'    => $orders->groupBy('status')->map->count(),
            'total_value'  => $orders->sum('total_amount'),
            'details'      => $orders->map(fn ($po) => [
                'po_number'  => $po->po_number,
                'supplier'   => $po->supplier?->name ?? 'N/A',
                'status'     => $po->status,
                'total'      => $po->total_amount,
                'currency'   => $po->currency,
                'issued_at'  => $po->issued_at,
                'created_at' => $po->created_at,
            ]),
        ];
    }

    public function exportProcurementReport(array $filters = [], string $format = 'csv'): StreamedResponse
    {
        $report = $this->getProcurementReport($filters);
        $headers = ['PO Number', 'Supplier', 'Status', 'Total (KES)', 'Currency', 'Issued At', 'Date'];
        $data = $report['details']->map(fn ($r) => [
            $r['po_number'], $r['supplier'], $r['status'],
            number_format($r['total'], 2), $r['currency'], $r['issued_at'], $r['created_at'],
        ])->toArray();

        return $this->streamCsv('procurement_report', $headers, $data);
    }

    // -------------------------------------------------------------------------
    // Supplier Reports
    // -------------------------------------------------------------------------

    public function getSupplierReport(array $filters = []): array
    {
        return $this->generateSupplierReport($filters);
    }

    public function generateSupplierReport(array $filters = []): array
    {
        $query = Supplier::query();

        if (!empty($filters['category_id'])) {
            $query->where('supplier_category_id', $filters['category_id']);
        }
        if (!empty($filters['status'])) {
            match ($filters['status']) {
                'approved'    => $query->where('is_approved', true)->where('is_blacklisted', false),
                'blacklisted' => $query->where('is_blacklisted', true),
                'pending'     => $query->where('is_approved', false),
                default       => null,
            };
        }

        $suppliers = $query->get();

        return [
            'title'                 => 'Supplier Register Report',
            'generated_at'          => now(),
            'total_suppliers'       => $suppliers->count(),
            'active_suppliers'      => $suppliers->where('is_approved', true)->where('is_blacklisted', false)->count(),
            'blacklisted_suppliers' => $suppliers->where('is_blacklisted', true)->count(),
            'pending_suppliers'     => $suppliers->where('is_approved', false)->count(),
            'details'               => $suppliers->map(fn ($s) => [
                'id'         => $s->id,
                'name'       => $s->name,
                'kra_pin'    => $s->kra_pin ?? 'N/A',
                'status'     => $s->is_blacklisted ? 'blacklisted' : ($s->is_approved ? 'active' : 'pending'),
                'email'      => $s->email,
                'created_at' => $s->created_at,
            ]),
        ];
    }

    public function getSupplierPerformanceReport(array $filters = []): array
    {
        $suppliers = Supplier::with(['purchaseOrders', 'performanceReviews'])
            ->where('is_approved', true)
            ->get();

        return [
            'title'        => 'Supplier Performance Report',
            'generated_at' => now(),
            'details'      => $suppliers->map(fn ($s) => [
                'supplier'             => $s->name,
                'total_orders'         => $s->purchaseOrders->count(),
                'total_value'          => $s->purchaseOrders->sum('total_amount'),
                'avg_rating'           => $s->performanceReviews->avg('overall_rating') ?? 'N/A',
                'on_time_delivery_pct' => $s->performanceReviews->avg('on_time_delivery_percent') ?? 0,
            ]),
        ];
    }

    public function exportSupplierReport(array $filters = [], string $format = 'csv'): StreamedResponse
    {
        $report = $this->getSupplierReport($filters);
        $headers = ['ID', 'Name', 'KRA PIN', 'Status', 'Email', 'Registered'];
        $data = $report['details']->map(fn ($r) => [
            $r['id'], $r['name'], $r['kra_pin'], $r['status'], $r['email'], $r['created_at'],
        ])->toArray();

        return $this->streamCsv('suppliers_report', $headers, $data);
    }

    // -------------------------------------------------------------------------
    // Inventory Reports
    // -------------------------------------------------------------------------

    public function getInventoryReport(array $filters = []): array
    {
        $query = InventoryItem::with('store', 'catalogItem');

        if (!empty($filters['store_id'])) $query->where('store_id', $filters['store_id']);
        if (!empty($filters['status'])) {
            match ($filters['status']) {
                'low_stock'    => $query->whereRaw('quantity <= reorder_level AND quantity > 0'),
                'out_of_stock' => $query->where('quantity', '<=', 0),
                'adequate'     => $query->whereRaw('quantity > reorder_level'),
                default        => null,
            };
        }

        $items = $query->get();
        $totalValue = $items->sum(fn ($i) => $i->quantity * $i->unit_cost);

        return [
            'title'        => 'Inventory Stock Report',
            'generated_at' => now(),
            'total_items'  => $items->count(),
            'total_value'  => $totalValue,
            'low_stock'    => $items->filter(fn ($i) => $i->quantity > 0 && $i->quantity <= $i->reorder_level)->count(),
            'out_of_stock' => $items->filter(fn ($i) => $i->quantity <= 0)->count(),
            'details'      => $items->map(fn ($i) => [
                'item'          => $i->catalogItem?->description ?? 'N/A',
                'store'         => $i->store?->name ?? 'N/A',
                'quantity'      => $i->quantity,
                'reorder_level' => $i->reorder_level,
                'unit_cost'     => $i->unit_cost,
                'total_value'   => $i->quantity * $i->unit_cost,
            ]),
        ];
    }

    public function generateInventoryReport(): array
    {
        return $this->getInventoryReport();
    }

    public function getInventoryValuationReport(array $filters = []): array
    {
        $query = InventoryItem::with('store', 'catalogItem');
        if (!empty($filters['store_id'])) $query->where('store_id', $filters['store_id']);

        $items = $query->get();

        return [
            'title'          => 'Inventory Valuation Report',
            'generated_at'   => now(),
            'valuation_date' => $filters['valuation_date'] ?? now()->toDateString(),
            'total_value'    => $items->sum(fn ($i) => $i->quantity * $i->unit_cost),
            'by_store'       => $items->groupBy('store_id')->map(fn ($grp) => [
                'store'       => $grp->first()->store?->name ?? 'Unknown',
                'item_count'  => $grp->count(),
                'total_value' => $grp->sum(fn ($i) => $i->quantity * $i->unit_cost),
            ])->values(),
        ];
    }

    public function getInventoryMovementsReport(array $filters = []): array
    {
        // InventoryTransaction may not exist as a standalone model in all setups;
        // fall back gracefully if it does not.
        if (!class_exists(InventoryTransaction::class)) {
            return ['title' => 'Inventory Movements Report', 'generated_at' => now(), 'details' => collect()];
        }

        $query = InventoryTransaction::with('inventoryItem.catalogItem', 'inventoryItem.store');

        if (!empty($filters['store_id'])) {
            $query->whereHas('inventoryItem', fn ($q) => $q->where('store_id', $filters['store_id']));
        }
        if (!empty($filters['transaction_type'])) $query->where('type', $filters['transaction_type']);
        if (!empty($filters['date_from']))         $query->whereDate('created_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))           $query->whereDate('created_at', '<=', $filters['date_to']);

        $transactions = $query->latest()->get();

        return [
            'title'        => 'Inventory Movements Report',
            'generated_at' => now(),
            'total_in'     => $transactions->where('type', 'in')->sum('quantity'),
            'total_out'    => $transactions->where('type', 'out')->sum('quantity'),
            'details'      => $transactions->map(fn ($t) => [
                'item'     => $t->inventoryItem?->catalogItem?->description ?? 'N/A',
                'store'    => $t->inventoryItem?->store?->name ?? 'N/A',
                'type'     => $t->type,
                'quantity' => $t->quantity,
                'notes'    => $t->notes,
                'date'     => $t->created_at,
            ]),
        ];
    }

    public function exportInventoryReport(array $filters = [], string $format = 'csv'): StreamedResponse
    {
        $report = $this->getInventoryReport($filters);
        $headers = ['Item', 'Store', 'Quantity', 'Reorder Level', 'Unit Cost (KES)', 'Total Value (KES)'];
        $data = $report['details']->map(fn ($r) => [
            $r['item'], $r['store'], $r['quantity'], $r['reorder_level'],
            number_format($r['unit_cost'], 2), number_format($r['total_value'], 2),
        ])->toArray();

        return $this->streamCsv('inventory_report', $headers, $data);
    }

    // -------------------------------------------------------------------------
    // Financial Reports
    // -------------------------------------------------------------------------

    public function getFinancialReport(array $filters = []): array
    {
        $fiscalYear = $filters['fiscal_year'] ?? now()->year;

        $invoices = SupplierInvoice::whereYear('invoice_date', $fiscalYear)->get();
        $payments = Payment::whereYear('created_at', $fiscalYear)->get();

        return [
            'title'              => "Financial Report — FY {$fiscalYear}",
            'generated_at'       => now(),
            'fiscal_year'        => $fiscalYear,
            'total_invoiced'     => $invoices->sum('total_amount'),
            'total_paid'         => $payments->where('status', 'completed')->sum('amount'),
            'total_wht'          => $invoices->sum('wht_amount'),
            'total_vat'          => $invoices->sum('vat_amount'),
            'invoices_by_status' => $invoices->groupBy('status')->map->count(),
            'payments_by_month'  => $payments->where('status', 'completed')
                ->groupBy(fn ($p) => $p->created_at->format('Y-m'))
                ->map->sum('amount'),
        ];
    }

    public function getBudgetReport(array $filters = []): array
    {
        $fiscalYear = (string) ($filters['fiscal_year'] ?? now()->year);
        return $this->generateBudgetReport($fiscalYear, $filters);
    }

    public function generateBudgetReport(string $fiscalYear, array $filters = []): array
    {
        $query = BudgetLine::where('fiscal_year', $fiscalYear)->with('department');

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        $lines = $query->get();
        $totalAllocated = $lines->sum('allocated_amount');
        $totalSpent     = $lines->sum('spent_amount');
        $totalCommitted = $lines->sum('committed_amount');

        return [
            'title'         => "Budget Utilization Report — FY {$fiscalYear}",
            'generated_at'  => now(),
            'fiscal_year'   => $fiscalYear,
            'summary'       => [
                'total_allocated'     => $totalAllocated,
                'total_spent'         => $totalSpent,
                'total_committed'     => $totalCommitted,
                'total_available'     => $totalAllocated - $totalSpent - $totalCommitted,
                'utilization_percent' => $totalAllocated > 0 ? round($totalSpent / $totalAllocated * 100, 2) : 0,
                'commitment_percent'  => $totalAllocated > 0 ? round($totalCommitted / $totalAllocated * 100, 2) : 0,
            ],
            'by_department' => $lines->map(fn ($l) => [
                'department'          => $l->department?->name ?? 'Unknown',
                'category'            => $l->category,
                'allocated'           => $l->allocated_amount,
                'committed'           => $l->committed_amount,
                'spent'               => $l->spent_amount,
                'available'           => $l->available_amount,
                'utilization_percent' => $l->utilization_percentage,
            ]),
        ];
    }

    public function getCashFlowReport(array $filters = []): array
    {
        $fiscalYear = $filters['fiscal_year'] ?? now()->year;

        $payments = Payment::where('status', 'completed')
            ->whereYear('created_at', $fiscalYear)
            ->get();

        $monthly = $payments->groupBy(fn ($p) => $p->created_at->format('Y-m'))
            ->map(fn ($grp) => ['count' => $grp->count(), 'amount' => $grp->sum('amount')]);

        return [
            'title'         => "Cash Flow Report — FY {$fiscalYear}",
            'generated_at'  => now(),
            'fiscal_year'   => $fiscalYear,
            'total_outflow' => $payments->sum('amount'),
            'monthly'       => $monthly,
        ];
    }

    public function getExpenditureReport(array $filters = []): array
    {
        $fiscalYear   = $filters['fiscal_year'] ?? now()->year;
        $departmentId = $filters['department_id'] ?? null;

        $query = BudgetLine::where('fiscal_year', $fiscalYear)->with('department');
        if ($departmentId) $query->where('department_id', $departmentId);

        $lines = $query->get();

        return [
            'title'        => "Expenditure Report — FY {$fiscalYear}",
            'generated_at' => now(),
            'fiscal_year'  => $fiscalYear,
            'total_spent'  => $lines->sum('spent_amount'),
            'total_budget' => $lines->sum('allocated_amount'),
            'details'      => $lines->map(fn ($l) => [
                'department' => $l->department?->name ?? 'Unknown',
                'category'   => $l->category,
                'allocated'  => $l->allocated_amount,
                'spent'      => $l->spent_amount,
                'variance'   => $l->allocated_amount - $l->spent_amount,
            ]),
        ];
    }

    public function getWHTReport(array $filters = []): array
    {
        $query = SupplierInvoice::where('wht_amount', '>', 0)->with('supplier');

        if (!empty($filters['date_from']))   $query->whereDate('invoice_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))     $query->whereDate('invoice_date', '<=', $filters['date_to']);
        if (!empty($filters['fiscal_year'])) $query->whereYear('invoice_date', $filters['fiscal_year']);

        $invoices = $query->get();

        return [
            'title'        => 'Withholding Tax (WHT) Report',
            'generated_at' => now(),
            'total_wht'    => $invoices->sum('wht_amount'),
            'total_gross'  => $invoices->sum('subtotal'),
            'details'      => $invoices->map(fn ($inv) => [
                'invoice_number' => $inv->invoice_number,
                'supplier'       => $inv->supplier?->name ?? 'N/A',
                'kra_pin'        => $inv->supplier?->kra_pin ?? 'N/A',
                'invoice_date'   => $inv->invoice_date,
                'gross_amount'   => $inv->subtotal,
                'wht_amount'     => $inv->wht_amount,
                'wht_rate_pct'   => $inv->subtotal > 0 ? round($inv->wht_amount / $inv->subtotal * 100, 2) : 0,
            ]),
        ];
    }

    public function exportFinancialReport(array $filters = [], string $format = 'csv'): StreamedResponse
    {
        $report  = $this->getFinancialReport($filters);
        $headers = ['Month', 'Payments (KES)'];
        $data    = collect($report['payments_by_month'])
            ->map(fn ($amount, $month) => [$month, number_format($amount, 2)])
            ->values()
            ->toArray();

        return $this->streamCsv('financial_report', $headers, $data);
    }

    // -------------------------------------------------------------------------
    // Performance & Compliance
    // -------------------------------------------------------------------------

    public function getPerformanceKPIs(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo   = $filters['date_to']   ?? now()->toDateString();

        $requisitions = Requisition::whereBetween('created_at', [$dateFrom, $dateTo])->get();
        $pos          = PurchaseOrder::whereBetween('created_at', [$dateFrom, $dateTo])->get();
        $payments     = Payment::where('status', 'completed')->whereBetween('created_at', [$dateFrom, $dateTo])->get();

        $avgApprovalDays = $requisitions->whereNotNull('approved_at')
            ->avg(fn ($r) => $r->created_at->diffInDays($r->approved_at));

        return [
            'title'                 => 'Performance KPIs',
            'period'                => "{$dateFrom} to {$dateTo}",
            'generated_at'          => now(),
            'total_requisitions'    => $requisitions->count(),
            'approved_requisitions' => $requisitions->where('status', 'approved')->count(),
            'total_pos'             => $pos->count(),
            'total_payments'        => $payments->count(),
            'total_payment_value'   => $payments->sum('amount'),
            'avg_approval_days'     => round($avgApprovalDays ?? 0, 1),
            'on_time_rate_pct'      => $requisitions->count() > 0
                ? round($requisitions->where('status', 'approved')->count() / $requisitions->count() * 100, 1)
                : 0,
        ];
    }

    public function getComplianceReport(array $filters = []): array
    {
        $fiscalYear = $filters['fiscal_year'] ?? now()->year;

        $totalReqs    = Requisition::whereYear('created_at', $fiscalYear)->count();
        $approvedReqs = Requisition::whereYear('created_at', $fiscalYear)->where('status', 'approved')->count();

        $totalInv    = SupplierInvoice::whereYear('invoice_date', $fiscalYear)->count();
        $matchedInv  = SupplierInvoice::whereYear('invoice_date', $fiscalYear)->where('three_way_match_passed', true)->count();

        $totalPay     = Payment::whereYear('created_at', $fiscalYear)->count();
        $completedPay = Payment::whereYear('created_at', $fiscalYear)->where('status', 'completed')->count();

        return [
            'title'                     => "Compliance Report — FY {$fiscalYear}",
            'generated_at'              => now(),
            'fiscal_year'               => $fiscalYear,
            'requisition_approval_rate' => $totalReqs > 0 ? round($approvedReqs / $totalReqs * 100, 1) : 0,
            'three_way_match_rate'      => $totalInv  > 0 ? round($matchedInv  / $totalInv  * 100, 1) : 0,
            'payment_completion_rate'   => $totalPay  > 0 ? round($completedPay / $totalPay  * 100, 1) : 0,
            'summary'                   => [
                'requisitions' => ['total' => $totalReqs,  'approved'   => $approvedReqs],
                'invoices'     => ['total' => $totalInv,   'matched'    => $matchedInv],
                'payments'     => ['total' => $totalPay,   'completed'  => $completedPay],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Invoice Aging (used by legacy code)
    // -------------------------------------------------------------------------

    public function generateInvoiceAgingReport(): array
    {
        $invoices = SupplierInvoice::where('status', '!=', 'rejected')->with('supplier')->get();
        $now = now();

        $buckets = ['aged_0_30' => [], 'aged_31_60' => [], 'aged_61_90' => [], 'aged_91_plus' => []];

        foreach ($invoices as $inv) {
            $days  = $inv->invoice_date->diffInDays($now);
            $entry = [
                'supplier'         => $inv->supplier?->name,
                'invoice_number'   => $inv->invoice_number,
                'amount'           => $inv->total_amount,
                'status'           => $inv->status,
                'days_outstanding' => $days,
                'due_date'         => $inv->due_date,
            ];
            if ($days <= 30)      $buckets['aged_0_30'][] = $entry;
            elseif ($days <= 60)  $buckets['aged_31_60'][] = $entry;
            elseif ($days <= 90)  $buckets['aged_61_90'][] = $entry;
            else                  $buckets['aged_91_plus'][] = $entry;
        }

        return array_merge(['title' => 'Invoice Aging Report', 'generated_at' => now()], $buckets);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function streamCsv(string $basename, array $headers, array $rows): StreamedResponse
    {
        $filename = $basename . '_' . now()->format('Ymd_His') . '.csv';

        return response()->stream(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // Kept for backwards compatibility with any direct calls to the old export stubs
    public function exportToCSV(array $reportData, string $filename): string  { return $filename; }
    public function exportToPDF(array $reportData, string $filename): string   { return $filename; }
    public function exportToExcel(array $reportData, string $filename): string { return $filename; }
}
