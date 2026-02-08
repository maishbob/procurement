<?php

namespace App\Events;

use App\Models\PurchaseOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderIssuedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public function __construct(
        public PurchaseOrder $purchaseOrder
    ) {
        $this->broadcastOn(new PrivateChannel('purchase-orders.' . $purchaseOrder->id));
    }

    public function broadcastAs(): string
    {
        return 'purchase-order.issued';
    }

    public function broadcastWith(): array
    {
        return [
            'purchase_order_id' => $this->purchaseOrder->id,
            'po_number' => $this->purchaseOrder->po_number,
            'supplier_id' => $this->purchaseOrder->supplier_id,
            'supplier_name' => $this->purchaseOrder->supplier?->name,
            'total_amount' => $this->purchaseOrder->total_amount,
            'issued_date' => $this->purchaseOrder->issued_date,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
