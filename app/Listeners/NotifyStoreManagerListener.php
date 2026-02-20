<?php

namespace App\Listeners;

use App\Events\LowStockDetectedEvent;
use App\Jobs\SendEmailNotificationJob;
use App\Models\User;
use App\Notifications\LowStockNotification;

class NotifyStoreManagerListener
{
    public function handle(LowStockDetectedEvent $event): void
    {
        $inventoryItem = $event->inventoryItem;

        // Find the Stores Manager — check the specific store first, then fall back to any Stores Manager
        $storeManager = null;
        $store = $inventoryItem->store ?? null;

        if ($store && $store->store_keeper_id) {
            $storeManager = User::active()->find($store->store_keeper_id);
        }

        if (!$storeManager) {
            $storeManager = User::active()
                ->whereHas('roles', fn ($q) => $q->where('name', 'Stores Manager'))
                ->first();
        }

        $notification = new LowStockNotification($inventoryItem, $event->quantityOnHand, $event->reorderLevel);

        if ($storeManager) {
            dispatch(new SendEmailNotificationJob($storeManager, $notification));
        }

        // Notify Procurement Officer and Procurement Assistant to initiate reordering
        $procurementUsers = User::active()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Procurement Officer', 'Procurement Assistant']))
            ->get();

        foreach ($procurementUsers as $user) {
            if (!$storeManager || $user->id !== $storeManager->id) {
                dispatch(new SendEmailNotificationJob($user, $notification));
            }
        }

        app(\App\Core\Audit\AuditService::class)->log(
            action: 'LOW_STOCK_ALERT_SENT',
            model: 'InventoryItem',
            modelId: $inventoryItem->id,
            metadata: [
                'item_name'          => $inventoryItem->catalogItem?->description ?? $inventoryItem->catalogItem?->name,
                'quantity_on_hand'   => $event->quantityOnHand,
                'reorder_level'      => $event->reorderLevel,
                'store_manager'      => $storeManager?->email,
                'procurement_count'  => $procurementUsers->count(),
            ],
        );
    }
}
