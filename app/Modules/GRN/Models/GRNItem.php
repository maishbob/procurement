<?php

namespace App\Modules\GRN\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GRNItem extends Model
{
    use HasFactory;

    protected $table = 'grn_items';

    protected $fillable = [
        'grn_id',
        'purchase_order_item_id',
        'line_number',
        'description',
        'quantity_ordered',
        'quantity_received',
        'quantity_accepted',
        'quantity_rejected',
        'rejection_reason',
        'unit_of_measure',
        'variance',
        'variance_reason',
        'quality_check_passed',
        'quality_notes',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'quantity_accepted' => 'integer',
        'quantity_rejected' => 'integer',
        'variance' => 'integer',
        'quality_check_passed' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\PurchaseOrders\Models\PurchaseOrderItem::class);
    }

    /**
     * Business Logic
     */
    public function getAcceptanceRateAttribute(): float
    {
        if ($this->quantity_received == 0) {
            return 0;
        }

        return ($this->quantity_accepted / $this->quantity_received) * 100;
    }

    public function hasVariance(): bool
    {
        return $this->quantity_received != $this->quantity_ordered;
    }

    public function hasRejections(): bool
    {
        return $this->quantity_rejected > 0;
    }

    /**
     * Formatted Attributes
     */
    public function getVarianceStatusAttribute(): string
    {
        if ($this->variance == 0) {
            return 'Match';
        } elseif ($this->variance > 0) {
            return 'Over Supply';
        } else {
            return 'Short Supply';
        }
    }

    public function getVarianceColorAttribute(): string
    {
        return match ($this->variance_status) {
            'Match' => 'green',
            'Over Supply' => 'yellow',
            'Short Supply' => 'red',
            default => 'gray',
        };
    }

    public function getQualityStatusAttribute(): string
    {
        if ($this->quality_check_passed === null) {
            return 'Pending';
        }

        return $this->quality_check_passed ? 'Passed' : 'Failed';
    }
}
