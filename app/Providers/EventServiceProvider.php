<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Requisition workflow
        \App\Events\RequisitionSubmittedEvent::class => [
            \App\Listeners\NotifyApproversListener::class,
        ],
        \App\Events\RequisitionApprovedEvent::class => [
            \App\Listeners\NotifyRequesterListener::class,
        ],

        // Purchase order workflow
        \App\Events\PurchaseOrderIssuedEvent::class => [
            \App\Listeners\NotifySupplierListener::class,
            \App\Listeners\UpdateBudgetListener::class . '@handlePOIssued',
        ],

        // GRN / inventory workflow
        \App\Events\GoodsReceivedEvent::class => [
            \App\Listeners\NotifyFinanceListener::class,
            \App\Listeners\UpdateInventoryListener::class,
        ],

        // Invoice / finance workflow
        \App\Events\InvoiceVerifiedEvent::class => [
            \App\Listeners\UpdateBudgetListener::class . '@handleInvoiceVerified',
        ],

        // Payment workflow — NotifyPaymentPartiesListener handles the payment;
        // NotifySupplierListener is only for PO issuance, not payments.
        \App\Events\PaymentProcessedEvent::class => [
            \App\Listeners\NotifyPaymentPartiesListener::class,
            \App\Listeners\UpdateBudgetListener::class . '@handlePaymentProcessed',
        ],

        // Budget alerts
        \App\Events\BudgetThresholdExceededEvent::class => [
            \App\Listeners\NotifyBudgetOwnerListener::class,
        ],

        // Inventory alerts
        \App\Events\LowStockDetectedEvent::class => [
            \App\Listeners\NotifyStoreManagerListener::class,
        ],
    ];

    /**
     * Model observers are fully registered in AppServiceProvider::boot().
     * Nothing additional is needed here.
     */
    public function boot(): void {}

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
