<?php

namespace App\Notifications;

use App\Models\InventoryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected InventoryItem $inventoryItem,
        protected int $quantityOnHand,
        protected int $reorderLevel
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['mail', 'database'];

        if ($notifiable->getUserPreferences()['notification_sms'] ?? false) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $catalogItem = $this->inventoryItem->catalogItem;
        $store = $this->inventoryItem->store;
        $reorderQty = $catalogItem?->reorder_quantity ?? 0;

        return (new MailMessage)
            ->subject("⚠ Low Stock Alert - {$catalogItem?->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Stock level for an item has fallen below the reorder point:")
            ->line("**Item Name:** {$catalogItem?->name}")
            ->line("**Item Code:** {$catalogItem?->item_code}")
            ->line("**Store:** {$store?->name}")
            ->line("**Current Stock:** {$this->quantityOnHand} units")
            ->line("**Reorder Level:** {$this->reorderLevel} units")
            ->line("**Suggested Reorder:** {$reorderQty} units")
            ->line("**Unit Cost:** KES " . number_format($catalogItem?->unit_cost ?? 0, 2))
            ->line("**Estimated Reorder Value:** KES " . number_format(($reorderQty * ($catalogItem?->unit_cost ?? 0)), 2))
            ->action('Create Purchase Requisition', route('requisitions.create'))
            ->line('Please arrange for stock replenishment as soon as possible.')
            ->line('Thank you,')
            ->line('Inventory Management System');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'low_stock_alert',
            'inventory_item_id' => $this->inventoryItem->id,
            'item_name' => $this->inventoryItem->catalogItem?->name,
            'item_code' => $this->inventoryItem->catalogItem?->item_code,
            'store_name' => $this->inventoryItem->store?->name,
            'quantity_on_hand' => $this->quantityOnHand,
            'reorder_level' => $this->reorderLevel,
            'summary' => "{$this->inventoryItem->catalogItem?->name} stock at {$this->quantityOnHand}/{$this->reorderLevel} units"
        ];
    }

    /**
     * Get the SMS notification message.
     */
    public function toSMS($notifiable): string
    {
        return "⚠ Low stock alert: {$this->inventoryItem->catalogItem?->name} " .
            "({$this->quantityOnHand}/{$this->reorderLevel} units) at {$this->inventoryItem->store?->name}. " .
            "Reorder required.";
    }
}
