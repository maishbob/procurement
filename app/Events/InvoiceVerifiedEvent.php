<?php

namespace App\Events;

use App\Models\SupplierInvoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceVerifiedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public function __construct(
        public SupplierInvoice $invoice
    ) {
        $this->broadcastOn(new PrivateChannel('invoices.' . $invoice->id));
    }

    public function broadcastAs(): string
    {
        return 'invoice.verified';
    }

    public function broadcastWith(): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'supplier_id' => $this->invoice->supplier_id,
            'supplier_name' => $this->invoice->supplier?->name,
            'amount' => $this->invoice->total_amount,
            'verified_date' => $this->invoice->verified_date,
            'three_way_match_status' => $this->invoice->three_way_match_status,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
