<?php

namespace App\Observers;

use App\Core\Audit\AuditService;
use App\Models\InventoryItem;

class InventoryItemObserver
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Handle the InventoryItem "created" event.
     */
    public function created(InventoryItem $item): void
    {
        $this->auditService->log(
            action: 'INVENTORY_ITEM_CREATED',
            status: 'success',
            model_type: 'InventoryItem',
            model_id: $item->id,
            description: "Inventory item {$item->description} created in {$item->store->name}",
            changes: [
                'catalog_item_id' => $item->catalog_item_id,
                'store_id' => $item->store_id,
                'quantity' => $item->quantity,
                'reorder_level' => $item->reorder_level,
                'unit_cost' => $item->unit_cost,
            ]
        );
    }

    /**
     * Handle the InventoryItem "adjusted" event.
     */
    public function adjusted(InventoryItem $item): void
    {
        $oldQty = $item->getOriginal('quantity');
        $newQty = $item->quantity;
        $adjustment = $newQty - $oldQty;

        $this->auditService->log(
            action: 'INVENTORY_ADJUSTED',
            status: 'success',
            model_type: 'InventoryItem',
            model_id: $item->id,
            description: "Inventory adjusted for {$item->description}. Change: {$adjustment} units ({$oldQty} → {$newQty})",
            changes: ['quantity' => ['from' => $oldQty, 'to' => $newQty]],
            metadata: [
                'adjustment_reason' => $item->adjustment_reason,
                'adjusted_by' => auth()?->id(),
            ]
        );
    }

    /**
     * Handle the InventoryItem "issued" event.
     */
    public function issued(InventoryItem $item): void
    {
        $this->auditService->log(
            action: 'INVENTORY_ISSUED',
            status: 'success',
            model_type: 'InventoryItem',
            model_id: $item->id,
            description: "Stock issued from {$item->description}",
            metadata: [
                'issued_by' => auth()?->id(),
                'issued_date' => now(),
            ]
        );
    }

    /**
     * Handle the InventoryItem "transferred" event.
     */
    public function transferred(InventoryItem $item): void
    {
        $this->auditService->log(
            action: 'INVENTORY_TRANSFERRED',
            status: 'success',
            model_type: 'InventoryItem',
            model_id: $item->id,
            description: "Inventory transferred for {$item->description}",
            metadata: [
                'transferred_by' => auth()?->id(),
                'transfer_date' => now(),
            ]
        );
    }

    /**
     * Handle the InventoryItem "updated" event.
     */
    public function updated(InventoryItem $item): void
    {
        $changes = [];
        foreach ($item->getChanges() as $key => $value) {
            if (!in_array($key, ['updated_at', 'adjustment_reason'])) {
                $changes[$key] = ['from' => $item->getOriginal($key), 'to' => $value];
            }
        }

        if (!empty($changes)) {
            $this->auditService->log(
                action: 'INVENTORY_ITEM_UPDATED',
                status: 'success',
                model_type: 'InventoryItem',
                model_id: $item->id,
                description: "Inventory item {$item->description} updated",
                changes: $changes
            );
        }
    }

    /**
     * Handle the InventoryItem "deleted" event.
     */
    public function deleted(InventoryItem $item): void
    {
        $this->auditService->log(
            action: 'INVENTORY_ITEM_DELETED',
            status: 'success',
            model_type: 'InventoryItem',
            model_id: $item->id,
            description: "Inventory item {$item->description} permanently deleted",
            changes: ['deleted_by' => auth()?->id()]
        );
    }
}
