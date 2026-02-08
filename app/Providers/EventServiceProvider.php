<?php

namespace App\Providers;

use App\Models\BudgetLine;
use App\Models\GoodsReceivedNote;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\Requisition;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Observers\BudgetLineObserver;
use App\Observers\GRNObserver;
use App\Observers\InventoryItemObserver;
use App\Observers\PaymentObserver;
use App\Observers\PurchaseOrderObserver;
use App\Observers\RequisitionObserver;
use App\Observers\SupplierInvoiceObserver;
use App\Observers\SupplierObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        \App\Events\RequisitionSubmittedEvent::class => [
            \App\Listeners\NotifyApproversListener::class,
        ],
        \App\Events\RequisitionApprovedEvent::class => [
            \App\Listeners\NotifyRequesterListener::class,
        ],
        \App\Events\PurchaseOrderIssuedEvent::class => [
            \App\Listeners\NotifySupplierListener::class,
            \App\Listeners\UpdateBudgetListener::class . '@handlePOIssued',
        ],
        \App\Events\GoodsReceivedEvent::class => [
            \App\Listeners\NotifyFinanceListener::class,
            \App\Listeners\UpdateInventoryListener::class,
        ],
        \App\Events\InvoiceVerifiedEvent::class => [
            \App\Listeners\UpdateBudgetListener::class . '@handleInvoiceVerified',
        ],
        \App\Events\PaymentProcessedEvent::class => [
            \App\Listeners\NotifySupplierListener::class,
            \App\Listeners\UpdateBudgetListener::class . '@handlePaymentProcessed',
        ],
        \App\Events\BudgetThresholdExceededEvent::class => [
            \App\Listeners\NotifyBudgetOwnerListener::class,
        ],
        \App\Events\LowStockDetectedEvent::class => [
            \App\Listeners\NotifyStoreManagerListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        /**
         * Register model observers
         * These automatically log all model events (created, updated, deleted) to audit log
         */
        // Temporarily disabled - missing models
        // Requisition::observe(RequisitionObserver::class);
        // PurchaseOrder::observe(PurchaseOrderObserver::class);
        // GoodsReceivedNote::observe(GRNObserver::class);
        // SupplierInvoice::observe(SupplierInvoiceObserver::class);
        // Payment::observe(PaymentObserver::class);
        // Supplier::observe(SupplierObserver::class);
        // InventoryItem::observe(InventoryItemObserver::class);
        BudgetLine::observe(BudgetLineObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
