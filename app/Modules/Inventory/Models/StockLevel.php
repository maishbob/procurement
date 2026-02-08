<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'store_id',
        'quantity_on_hand',
        'quantity_allocated',
        'quantity_available',
        'quantity_on_order',
        'value',
        'last_movement_at',
        'last_count_at',
    ];

    protected $casts = [
        'quantity_on_hand' => 'decimal:3',
        'quantity_allocated' => 'decimal:3',
        'quantity_available' => 'decimal:3',
        'quantity_on_order' => 'decimal:3',
        'value' => 'decimal:2',
        'last_movement_at' => 'datetime',
        'last_count_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Store::class, 'store_id');
    }
}
