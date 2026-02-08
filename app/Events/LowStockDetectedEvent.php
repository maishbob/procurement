<?php

namespace App\Events;

use App\Models\InventoryItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockDetectedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public function __construct(
        public InventoryItem $inventoryItem,
        public int $quantityOnHand,
        public int $reorderLevel
    ) {
        $this->broadcastOn(new PrivateChannel('inventory.' . $inventoryItem->id));
    }

    public function broadcastAs(): string
    {
        return 'inventory.low-stock-detected';
    }

    public function broadcastWith(): array
    {
        // Get first stock level for this item to determine store
        $stockLevel = $this->inventoryItem->stockLevels()->with('store')->first();
        
        return [
            'inventory_item_id' => $this->inventoryItem->id,
            'item_name' => $this->inventoryItem->name,
            'item_code' => $this->inventoryItem->item_code,
            'store_id' => $stockLevel?->store_id,
            'store_name' => $stockLevel?->store?->name,
            'quantity_on_hand' => $this->quantityOnHand,
            'reorder_level' => $this->reorderLevel,
            'reorder_quantity' => $this->inventoryItem->reorder_quantity,
            'unit_cost' => $this->inventoryItem->standard_cost,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
