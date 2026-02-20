<?php

namespace App\Listeners;

use App\Events\GoodsReceivedEvent;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Models\StockTransaction;

class UpdateInventoryListener
{
    /**
     * Handle the GoodsReceivedEvent
     */
    public function handle(GoodsReceivedEvent $event): void
    {
        $grn = $event->grn;

        // Update inventory levels based on GRN items
        foreach ($grn->items as $grnItem) {
            try {
                $this->updateInventoryItem($grn, $grnItem);

                // Check if stock is low after update
                $this->checkLowStock($grnItem, $grn);
            } catch (\Exception $e) {
                app(\App\Core\Audit\AuditService::class)->log(
                    'INVENTORY_UPDATE_FAILED',
                    'GRNItem',
                    $grnItem->id,
                    null,
                    null,
                    "Failed to update inventory: {$e->getMessage()}",
                    [
                        'error' => $e->getMessage(),
                        'grn_item_id' => $grnItem->id,
                    ]
                );
            }
        }
    }

    /**
     * Update individual inventory item from GRN
     */
    protected function updateInventoryItem($grn, $grnItem): void
    {
        // Get the catalog item (inventory item master)
        $catalogItem = $grnItem->catalogItem ?? $grnItem->inventory_item;
        if (!$catalogItem) {
            throw new \Exception("Catalog item not found for GRN item");
        }

        // Ensure we have a StockLevel record for this item in the receiving store
        $stockLevel = StockLevel::firstOrCreate(
            [
                'inventory_item_id' => $catalogItem->id,
                'store_id' => $grn->to_store_id ?? $grn->store_id ?? 1,
            ],
            [
                'quantity_on_hand' => 0,
                'quantity_allocated' => 0,
                'quantity_available' => 0,
                'quantity_on_order' => 0,
                'value' => 0,
            ]
        );

        // Update stock quantity
        $oldQty = $stockLevel->quantity_on_hand;
        $newQty = $oldQty + $grnItem->quantity_received;
        
        $stockLevel->update([
            'quantity_on_hand' => $newQty,
            'quantity_available' => $newQty - $stockLevel->quantity_allocated,
            'value' => $newQty * ($grnItem->unit_cost ?? 0),
            'last_movement_at' => now(),
        ]);

        // Record stock transaction
        StockTransaction::create([
            'transaction_number' => 'STX-' . uniqid(),
            'inventory_item_id' => $catalogItem->id,
            'store_id' => $stockLevel->store_id,
            'transaction_type' => 'receipt',
            'quantity' => $grnItem->quantity_received,
            'unit_of_measure' => $catalogItem->unit_of_measure,
            'unit_cost' => $grnItem->unit_cost,
            'total_value' => $grnItem->quantity_received * ($grnItem->unit_cost ?? 0),
            'reference_type' => 'GoodsReceivedNote',
            'reference_id' => $grn->id,
            'reference_number' => $grn->grn_number ?? 'GRN-' . $grn->id,
            'status' => 'posted',
            'notes' => "Received from GRN #{$grn->grn_number}",
            'created_by' => auth()?->id() ?? 1,
            'transaction_date' => now(),
        ]);

        // Audit log
        app(\App\Core\Audit\AuditService::class)->log(
            'INVENTORY_ITEM_UPDATED',
            'InventoryItem',
            $catalogItem->id,
            null,
            null,
            "Inventory updated from GRN {$grn->grn_number}",
            [
                'quantity_received' => $grnItem->quantity_received,
                'old_quantity' => $oldQty,
                'new_quantity' => $newQty,
                'store_id' => $stockLevel->store_id,
                'unit_cost' => $grnItem->unit_cost,
            ]
        );
    }

    /**
     * Check if inventory level is low
     */
    protected function checkLowStock($grnItem, $grn): void
    {
        $catalogItem = $grnItem->catalogItem ?? $grnItem->inventory_item;
        if (!$catalogItem || !$catalogItem->reorder_point) {
            return;
        }

        // Check stock level in the store that received the goods
        $storeId = $grn->to_store_id ?? $grn->store_id ?? 1;
        $stockLevel = StockLevel::where('inventory_item_id', $catalogItem->id)
            ->where('store_id', $storeId)
            ->first();

        if ($stockLevel && $stockLevel->quantity_on_hand <= $catalogItem->reorder_point) {
            event(new \App\Events\LowStockDetectedEvent(
                $catalogItem,
                $stockLevel->quantity_on_hand,
                $catalogItem->reorder_point
            ));
        }
    }
}
