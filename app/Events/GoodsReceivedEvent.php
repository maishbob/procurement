<?php

namespace App\Events;

use App\Models\GoodsReceivedNote;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GoodsReceivedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public function __construct(
        public GoodsReceivedNote $grn
    ) {
        $this->broadcastOn(new PrivateChannel('grns.' . $grn->id));
    }

    public function broadcastAs(): string
    {
        return 'goods-received.recorded';
    }

    public function broadcastWith(): array
    {
        return [
            'grn_id' => $this->grn->id,
            'grn_number' => $this->grn->grn_number,
            'purchase_order_id' => $this->grn->purchase_order_id,
            'supplier_id' => $this->grn->purchaseOrder?->supplier_id,
            'items_count' => $this->grn->items->count(),
            'received_date' => $this->grn->received_date,
            'status' => $this->grn->status,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
