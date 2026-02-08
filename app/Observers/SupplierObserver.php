<?php

namespace App\Observers;

use App\Core\Audit\AuditService;
use App\Models\Supplier;

class SupplierObserver
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Handle the Supplier "created" event.
     */
    public function created(Supplier $supplier): void
    {
        $this->auditService->log(
            action: 'SUPPLIER_CREATED',
            status: 'success',
            model_type: 'Supplier',
            model_id: $supplier->id,
            description: "Supplier {$supplier->name} created with KRA PIN {$supplier->kra_pin}",
            changes: [
                'name' => $supplier->name,
                'kra_pin' => $supplier->kra_pin,
                'tax_compliance_status' => $supplier->tax_compliance_status,
                'is_blacklisted' => false,
            ],
            metadata: [
                'created_by' => auth()?->id(),
                'supplier_category' => $supplier->category?->name,
            ]
        );
    }

    /**
     * Handle the Supplier "updated" event.
     */
    public function updated(Supplier $supplier): void
    {
        $changes = [];
        foreach ($supplier->getChanges() as $key => $value) {
            if (!in_array($key, ['updated_at'])) {
                $changes[$key] = ['from' => $supplier->getOriginal($key), 'to' => $value];
            }
        }

        if (!empty($changes)) {
            $this->auditService->log(
                action: 'SUPPLIER_UPDATED',
                status: 'success',
                model_type: 'Supplier',
                model_id: $supplier->id,
                description: "Supplier {$supplier->name} information updated",
                changes: $changes
            );
        }
    }

    /**
     * Handle the Supplier "blacklisted" event.
     */
    public function blacklisted(Supplier $supplier): void
    {
        $this->auditService->log(
            action: 'SUPPLIER_BLACKLISTED',
            status: 'success',
            model_type: 'Supplier',
            model_id: $supplier->id,
            description: "Supplier {$supplier->name} blacklisted due to {$supplier->blacklist_reason}",
            changes: ['is_blacklisted' => ['from' => false, 'to' => true]],
            metadata: [
                'blacklist_reason' => $supplier->blacklist_reason,
                'blacklisted_by' => auth()?->id(),
                'blacklist_date' => now(),
            ]
        );
    }

    /**
     * Handle the Supplier "unblacklisted" event.
     */
    public function unblacklisted(Supplier $supplier): void
    {
        $this->auditService->log(
            action: 'SUPPLIER_UNBLACKLISTED',
            status: 'success',
            model_type: 'Supplier',
            model_id: $supplier->id,
            description: "Supplier {$supplier->name} removed from blacklist",
            changes: ['is_blacklisted' => ['from' => true, 'to' => false]],
            metadata: [
                'unblacklisted_by' => auth()?->id(),
                'unblacklist_date' => now(),
            ]
        );
    }

    /**
     * Handle the Supplier "deleted" event.
     */
    public function deleted(Supplier $supplier): void
    {
        $this->auditService->log(
            action: 'SUPPLIER_DELETED',
            status: 'success',
            model_type: 'Supplier',
            model_id: $supplier->id,
            description: "Supplier {$supplier->name} permanently deleted",
            changes: ['deleted_by' => auth()?->id()]
        );
    }
}
