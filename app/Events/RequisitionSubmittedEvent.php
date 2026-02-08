<?php

namespace App\Events;

use App\Models\Requisition;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequisitionSubmittedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public function __construct(
        public Requisition $requisition
    ) {
        $this->broadcastOn(new PrivateChannel('requisitions.' . $requisition->id));
    }

    public function broadcastAs(): string
    {
        return 'requisition.submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'requisition_id' => $this->requisition->id,
            'requisition_number' => $this->requisition->requisition_number,
            'submitted_by' => $this->requisition->created_by,
            'total_amount' => $this->requisition->total_amount,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
