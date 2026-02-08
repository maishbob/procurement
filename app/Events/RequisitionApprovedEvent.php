<?php

namespace App\Events;

use App\Models\Requisition;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequisitionApprovedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public function __construct(
        public Requisition $requisition,
        public string $approvalLevel = '',
        public string $approverName = ''
    ) {
        $this->broadcastOn(new PrivateChannel('requisitions.' . $requisition->id));
    }

    public function broadcastAs(): string
    {
        return 'requisition.approved';
    }

    public function broadcastWith(): array
    {
        return [
            'requisition_id' => $this->requisition->id,
            'requisition_number' => $this->requisition->requisition_number,
            'approval_level' => $this->approvalLevel,
            'approver_name' => $this->approverName,
            'status' => $this->requisition->status,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
