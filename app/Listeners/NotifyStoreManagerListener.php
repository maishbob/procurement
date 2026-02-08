<?php

namespace App\Listeners;

use App\Events\LowStockDetectedEvent;
use App\Jobs\SendEmailNotificationJob;
use App\Notifications\LowStockNotification;

class NotifyStoreManagerListener
{
    /**
     * Handle the event.
     */
    public function handle(LowStockDetectedEvent $event): void
    {
        $inventoryItem = $event->inventoryItem;
        
        // Get store from stock levels - use first store where this item has low stock
        $reorderPoint = $inventoryItem->reorder_point ?? 0;
        $stockLevel = $inventoryItem->stockLevels()
            ->where('quantity_on_hand', '<=', $reorderPoint)
            ->with('store')
            ->first();
        
        if (!$stockLevel || !$stockLevel->store) {
            return;
        }
        
        $store = $stockLevel->store;

        // Find store manager - check if store has assigned store_keeper
        $storeManager = null;
        if ($store->store_keeper_id) {
            $storeManager = \App\Models\User::find($store->store_keeper_id);
        }
        
        if (!$storeManager) {
            // Find any store manager by role
            $storeManager = \App\Models\User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['store_manager', 'store_keeper']);
            })->first();
        }

        if (!$storeManager) {
            return;
        }

        // Send low stock alert
        dispatch(new SendEmailNotificationJob(
            $storeManager,
            new LowStockNotification($inventoryItem, $event->quantityOnHand, $event->reorderLevel)
        ));

        // Also notify procurement team for reordering
        $procurementUsers = \App\Models\User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['procurement', 'procurement_officer', 'manager']);
        })->get();

        foreach ($procurementUsers as $user) {
            dispatch(new SendEmailNotificationJob(
                $user,
                new LowStockNotification($inventoryItem, $event->quantityOnHand, $event->reorderLevel)
            ));
        }

        // Audit log
        \App\Core\Audit\AuditService::log(
            action: 'LOW_STOCK_ALERT_SENT',
            status: 'success',
            model_type: 'InventoryItem',
            model_id: $inventoryItem->id,
            description: "Low stock alert sent for {$inventoryItem->catalogItem?->name}",
            metadata: [
                'inventory_item_id' => $inventoryItem->id,
                'item_name' => $inventoryItem->catalogItem?->name,
                'quantity_on_hand' => $event->quantityOnHand,
                'reorder_level' => $event->reorderLevel,
                'store_id' => $store->id,
                'recipients' => [
                    'store_manager' => $storeManager->email,
                    'procurement_count' => $procurementUsers->count(),
                ]
            ]
        );
    }
}
