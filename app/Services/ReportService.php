<?php

namespace App\Services;

use App\Models\Requisition;
use App\Models\PurchaseOrder;
use App\Models\SupplierInvoice;
use App\Models\Payment;
use App\Models\BudgetLine;
use App\Models\InventoryItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ReportService
{
    /**
     * Generate requisition status report
     */
    public function generateRequisitionReport(array $filters = []): array
    {
        $query = Requisition::with('requester', 'department', 'approvals');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $requisitions = $query->get();

        return [
            'title' => 'Requisition Status Report',
            'generated_at' => now(),
            'total_requisitions' => $requisitions->count(),
            'by_status' => $requisitions->groupBy('status')->map->count(),
            'total_value' => $requisitions->sum(function ($r) {
                return $r->items->sum('unit_price');
            }),
            'pending_approval' => $requisitions->where('status', 'pending_approval')->count(),
            'details' => $requisitions->map(function ($r) {
                return [
                    'requisition_id' => $r->id,
                    'number' => $r->requisition_number,
                    'department' => $r->department->name,
                    'requester' => $r->requester->name,
                    'status' => $r->status,
                    'total_value' => $r->items->sum(function ($item) {
                        return $item->quantity * $item->unit_price;
                    }),
                    'items_count' => $r->items->count(),
                    'created_at' => $r->created_at,
                ];
            }),
        ];
    }

    /**
     * Generate procurement process report (RFQ/RFP/Tender)
     */
    public function generateProcurementReport(array $filters = []): array
    {
        $query = \App\Models\ProcurementProcess::with('bids', 'awardedSupplier');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $processes = $query->get();

        return [
            'title' => 'Procurement Process Report',
            'generated_at' => now(),
            'total_processes' => $processes->count(),
            'by_type' => $processes->groupBy('type')->map->count(),
            'by_status' => $processes->groupBy('status')->map->count(),
            'total_awarded_value' => $processes->whereNotNull('awarded_amount')->sum('awarded_amount'),
            'details' => $processes->map(function ($p) {
                return [
                    'title' => $p->title,
                    'type' => $p->type,
                    'status' => $p->status,
                    'budget' => $p->budget_allocation,
                    'awarded_amount' => $p->awarded_amount,
                    'bid_count' => $p->bids->count(),
                    'winning_supplier' => $p->awardedSupplier?->name,
                    'created_at' => $p->created_at,
                ];
            }),
        ];
    }

    /**
     * Generate budget utilization report
     */
    public function generateBudgetReport(string $fiscalYear, array $filters = []): array
    {
        $query = BudgetLine::where('fiscal_year', $fiscalYear)->with('costCenter');

        if (!empty($filters['department_id'])) {
            $query->whereHas('costCenter', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        $budgetLines = $query->get();

        $totalAllocated = $budgetLines->sum('allocated_amount');
        $totalSpent = $budgetLines->sum('spent_amount');
        $totalCommitted = $budgetLines->sum('committed_amount');

        return [
            'title' => "Budget Utilization Report - FY {$fiscalYear}",
            'generated_at' => now(),
            'fiscal_year' => $fiscalYear,
            'summary' => [
                'total_allocated' => $totalAllocated,
                'total_spent' => $totalSpent,
                'total_committed' => $totalCommitted,
                'total_available' => $totalAllocated - $totalSpent - $totalCommitted,
                'utilization_percent' => $totalAllocated > 0 ? ($totalSpent / $totalAllocated * 100) : 0,
                'commitment_percent' => $totalAllocated > 0 ? ($totalCommitted / $totalAllocated * 100) : 0,
            ],
            'by_cost_center' => $budgetLines->map(function ($line) {
                return [
                    'cost_center' => $line->costCenter->name,
                    'allocated' => $line->allocated_amount,
                    'spent' => $line->spent_amount,
                    'committed' => $line->committed_amount,
                    'available' => $line->available_amount,
                    'utilization_percent' => $line->utilization_percent,
                ];
            }),
        ];
    }

    /**
     * Generate supplier performance report
     */
    public function generateSupplierReport(array $filters = []): array
    {
        $query = Supplier::with('performanceReviews', 'purchaseOrders');

        if (!empty($filters['category_id'])) {
            $query->where('supplier_category_id', $filters['category_id']);
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_approved', true)->where('is_blacklisted', false);
            } elseif ($filters['status'] === 'blacklisted') {
                $query->where('is_blacklisted', true);
            }
        }

        $suppliers = $query->get();

        return [
            'title' => 'Supplier Performance Report',
            'generated_at' => now(),
            'total_suppliers' => $suppliers->count(),
            'active_suppliers' => $suppliers->where('is_approved', true)->where('is_blacklisted', false)->count(),
            'blacklisted_suppliers' => $suppliers->where('is_blacklisted', true)->count(),
            'details' => $suppliers->map(function ($supplier) {
                $reviews = $supplier->performanceReviews()->latest()->limit(5)->get();

                return [
                    'supplier_id' => $supplier->id,
                    'name' => $supplier->name,
                    'kra_pin' => $supplier->kra_pin,
                    'status' => $supplier->is_blacklisted ? 'blacklisted' : ($supplier->is_approved ? 'active' : 'pending'),
                    'compliance_status' => $supplier->tax_compliance_status,
                    'total_po_count' => $supplier->purchaseOrders->count(),
                    'total_spent' => $supplier->invoices()->sum('total_amount'),
                    'average_rating' => $reviews->avg('overall_rating') ?? 'N/A',
                    'on_time_delivery_percent' => $reviews->avg('on_time_delivery_percent') ?? '0%',
                ];
            }),
        ];
    }

    /**
     * Generate invoice aging report
     */
    public function generateInvoiceAgingReport(int $daysBuckets = 30): array
    {
        $query = SupplierInvoice::where('status', '!=', 'rejected')->with('supplier', 'purchaseOrder');

        $invoices = $query->get();

        $now = now();
        $aged_0_30 = [];
        $aged_31_60 = [];
        $aged_61_90 = [];
        $aged_91_plus = [];

        foreach ($invoices as $invoice) {
            $daysPassed = $invoice->invoice_date->diffInDays($now);

            $data = [
                'supplier' => $invoice->supplier->name,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->total_amount,
                'status' => $invoice->status,
                'days_outstanding' => $daysPassed,
                'due_date' => $invoice->due_date,
            ];

            if ($daysPassed <= 30) {
                $aged_0_30[] = $data;
            } elseif ($daysPassed <= 60) {
                $aged_31_60[] = $data;
            } elseif ($daysPassed <= 90) {
                $aged_61_90[] = $data;
            } else {
                $aged_91_plus[] = $data;
            }
        }

        return [
            'title' => 'Invoice Aging Report',
            'generated_at' => now(),
            'summary' => [
                '0-30_days' => ['count' => count($aged_0_30), 'amount' => array_sum(array_column($aged_0_30, 'amount'))],
                '31-60_days' => ['count' => count($aged_31_60), 'amount' => array_sum(array_column($aged_31_60, 'amount'))],
                '61-90_days' => ['count' => count($aged_61_90), 'amount' => array_sum(array_column($aged_61_90, 'amount'))],
                '91+_days' => ['count' => count($aged_91_plus), 'amount' => array_sum(array_column($aged_91_plus, 'amount'))],
            ],
            'aged_0_30' => $aged_0_30,
            'aged_31_60' => $aged_31_60,
            'aged_61_90' => $aged_61_90,
            'aged_91_plus' => $aged_91_plus,
        ];
    }

    /**
     * Generate inventory valuation report
     */
    public function generateInventoryReport(): array
    {
        $items = InventoryItem::with('store', 'catalogItem')->get();

        $totalValue = $items->sum(function ($item) {
            return $item->quantity * $item->unit_cost;
        });

        return [
            'title' => 'Inventory Valuation Report',
            'generated_at' => now(),
            'total_items' => $items->count(),
            'total_value' => $totalValue,
            'by_store' => $items->groupBy('store_id')->map(function ($storeItems) {
                return [
                    'store' => $storeItems->first()->store->name,
                    'item_count' => $storeItems->count(),
                    'total_value' => $storeItems->sum(function ($item) {
                        return $item->quantity * $item->unit_cost;
                    }),
                ];
            }),
            'low_stock_items' => $items->filter(function ($item) {
                return $item->quantity <= $item->reorder_level;
            })->map(function ($item) {
                return [
                    'item' => $item->catalogItem->description,
                    'current_qty' => $item->quantity,
                    'reorder_level' => $item->reorder_level,
                    'shortage' => $item->reorder_level - $item->quantity,
                ];
            }),
        ];
    }

    /**
     * Export report to CSV
     */
    public function exportToCSV(array $reportData, string $filename): string
    {
        // Implementation for CSV export - could use Laravel Excel or similar
        return $filename;
    }

    /**
     * Export report to PDF
     */
    public function exportToPDF(array $reportData, string $filename): string
    {
        // Implementation for PDF export - could use DomPDF or similar
        return $filename;
    }

    /**
     * Export report to Excel
     */
    public function exportToExcel(array $reportData, string $filename): string
    {
        // Implementation for Excel export - could use Laravel Excel
        return $filename;
    }
}
