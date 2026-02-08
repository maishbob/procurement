<?php

namespace App\Modules\PurchaseOrders\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'requisition_item_id',
        'line_number',
        'description',
        'specifications',
        'quantity',
        'unit_of_measure',
        'unit_price',
        'line_subtotal',
        'vat_rate',
        'vat_amount',
        'line_total',
        'is_vatable',
        'vat_type',
        'subject_to_wht',
        'wht_type',
        'quantity_received',
        'quantity_outstanding',
        'receiving_status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'is_vatable' => 'boolean',
        'subject_to_wht' => 'boolean',
        'quantity_received' => 'integer',
        'quantity_outstanding' => 'integer',
    ];

    /**
     * Relationships
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function requisitionItem(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Requisitions\Models\RequisitionItem::class);
    }

    /**
     * Business Logic
     */
    public function getReceivedPercentageAttribute(): float
    {
        if ($this->quantity == 0) {
            return 0;
        }

        return ($this->quantity_received / $this->quantity) * 100;
    }

    public function isFullyReceived(): bool
    {
        return $this->quantity_received >= $this->quantity;
    }

    public function isPartiallyReceived(): bool
    {
        return $this->quantity_received > 0 && $this->quantity_received < $this->quantity;
    }

    /**
     * Formatted Attributes
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2);
    }

    public function getFormattedLineTotalAttribute(): string
    {
        return number_format($this->line_total, 2);
    }

    public function getReceivingStatusLabelAttribute(): string
    {
        if ($this->isFullyReceived()) {
            return 'Fully Received';
        } elseif ($this->isPartiallyReceived()) {
            return 'Partially Received';
        } else {
            return 'Pending';
        }
    }
}
