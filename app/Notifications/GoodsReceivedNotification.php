<?php

namespace App\Notifications;

use App\Models\GoodsReceivedNote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GoodsReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected GoodsReceivedNote $grn
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $po = $this->grn->purchaseOrder;
        $supplier = $po?->supplier;
        $itemsCount = $this->grn->items->count();

        return (new MailMessage)
            ->subject("✓ Goods Received - GRN #{$this->grn->grn_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Goods have been received and logged in the system:")
            ->line("**GRN Number:** {$this->grn->grn_number}")
            ->line("**Purchase Order:** {$po?->po_number}")
            ->line("**Supplier:** {$supplier?->name}")
            ->line("**Received Date:** {$this->grn->received_date?->format('d/m/Y H:i')}")
            ->line("**Items Count:** {$itemsCount}")
            ->line("**Current Status:** " . ucfirst($this->grn->status))
            ->line("")
            ->line("**Next Steps:**")
            ->line("1. Quality inspection of received items (if required)")
            ->line("2. Post goods to inventory")
            ->line("3. Process supplier invoice for payment")
            ->action('View GRN Details', route('grn.show', $this->grn))
            ->line('Thank you,')
            ->line('Logistics Management');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'goods_received',
            'grn_id' => $this->grn->id,
            'grn_number' => $this->grn->grn_number,
            'po_number' => $this->grn->purchaseOrder?->po_number,
            'supplier_name' => $this->grn->purchaseOrder?->supplier?->name,
            'items_count' => $this->grn->items->count(),
            'received_date' => $this->grn->received_date,
            'status' => $this->grn->status,
            'summary' => "GRN #{$this->grn->grn_number} recorded with {$this->grn->items->count()} items"
        ];
    }
}
