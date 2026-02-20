<?php

namespace App\Providers;

use App\Models\BudgetLine;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\Requisition;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Modules\GRN\Models\GoodsReceivedNote;
use App\Modules\Inventory\Models\InventoryItem;
use App\Observers\BudgetLineObserver;
use App\Observers\GRNObserver;
use App\Observers\InventoryItemObserver;
use App\Observers\PaymentObserver;
use App\Observers\PurchaseOrderObserver;
use App\Observers\RequisitionObserver;
use App\Observers\SupplierInvoiceObserver;
use App\Observers\SupplierObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            \App\Modules\Planning\Services\AnnualProcurementPlanService::class,
            function ($app) {
                return new \App\Modules\Planning\Services\AnnualProcurementPlanService(
                    $app->make(\App\Core\Audit\AuditService::class),
                    $app->make(\App\Core\Workflow\WorkflowEngine::class)
                );
            }
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Requisition::observe(RequisitionObserver::class);
        BudgetLine::observe(BudgetLineObserver::class);
        GoodsReceivedNote::observe(GRNObserver::class);
        Payment::observe(PaymentObserver::class);
        PurchaseOrder::observe(PurchaseOrderObserver::class);
        SupplierInvoice::observe(SupplierInvoiceObserver::class);
        Supplier::observe(SupplierObserver::class);
        InventoryItem::observe(InventoryItemObserver::class);
    }
}
