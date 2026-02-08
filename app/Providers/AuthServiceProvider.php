<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\BudgetLine;
use App\Models\GoodsReceivedNote;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\Requisition;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\BudgetLinePolicy;
use App\Policies\GRNPolicy;
use App\Policies\InventoryPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\RequisitionPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AuditLog::class => AuditLogPolicy::class,
        BudgetLine::class => BudgetLinePolicy::class,
        GoodsReceivedNote::class => GRNPolicy::class,
        InventoryItem::class => InventoryPolicy::class,
        Payment::class => PaymentPolicy::class,
        PurchaseOrder::class => PurchaseOrderPolicy::class,
        Requisition::class => RequisitionPolicy::class,
        Supplier::class => SupplierPolicy::class,
        SupplierInvoice::class => InvoicePolicy::class,
        User::class => UserPolicy::class,
        \App\Models\ProcurementProcess::class => \App\Policies\ProcurementProcessPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // All policies are registered either through the policies array above
        // (automatic policy resolution) or can be explicitly registered here if needed
    }
}
