<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderIssuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected PurchaseOrder $purchaseOrder
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
        $supplier = $this->purchaseOrder->supplier;
        $items = $this->purchaseOrder->items;

        $message = (new MailMessage)
            ->subject("Purchase Order #{$this->purchaseOrder->po_number}")
            ->greeting("Good day {$supplier?->name},")
            ->line("You have been awarded a purchase order as follows:")
            ->line("**PO Number:** {$this->purchaseOrder->po_number}")
            ->line("**Delivery Address:** {$this->purchaseOrder->delivery_location}")
            ->line("**Delivery Date:** {$this->purchaseOrder->delivery_date?->format('d/m/Y')}")
            ->line("")
            ->line("**Order Items:**");

        // Add items table
        foreach ($items as $item) {
            $message->line("• {$item->catalogItem?->name} - Qty: {$item->quantity} @ KES {$item->unit_price}");
        }

        $message->line("")
            ->line("**Total Amount:** KES " . number_format($this->purchaseOrder->total_amount, 2))
            ->line("**Payment Terms:** {$this->purchaseOrder->payment_terms}")
            ->line("")
            ->line("Please acknowledge receipt of this PO by the specified date.")
            ->line('Thank you.');

        return $message;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'purchase_order_issued',
            'purchase_order_id' => $this->purchaseOrder->id,
            'po_number' => $this->purchaseOrder->po_number,
            'supplier_name' => $this->purchaseOrder->supplier?->name,
            'amount' => $this->purchaseOrder->total_amount,
            'items_count' => count($this->purchaseOrder->items),
            'delivery_date' => $this->purchaseOrder->delivery_date,
            'summary' => "PO #{$this->purchaseOrder->po_number} issued for KES {$this->purchaseOrder->total_amount}"
        ];
    }
}
