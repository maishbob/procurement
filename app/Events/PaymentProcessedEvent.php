<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentProcessedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public function __construct(
        public Payment $payment
    ) {
        $this->broadcastOn(new PrivateChannel('payments.' . $payment->id));
    }

    public function broadcastAs(): string
    {
        return 'payment.processed';
    }

    public function broadcastWith(): array
    {
        return [
            'payment_id' => $this->payment->id,
            'payment_reference' => $this->payment->reference_number,
            'supplier_id' => $this->payment->supplier_id,
            'supplier_name' => $this->payment->supplier?->name,
            'amount' => $this->payment->amount,
            'wht_amount' => $this->payment->withholding_tax_amount,
            'net_amount' => $this->payment->net_amount,
            'processed_date' => $this->payment->processed_date,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
