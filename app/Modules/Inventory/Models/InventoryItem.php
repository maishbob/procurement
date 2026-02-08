<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'item_code',
        'name',
        'description',
        'category_id',
        'unit_of_measure',
        'is_consumable',
        'is_asset',
        'asset_category',
        'reorder_level',
        'minimum_stock_level',
        'maximum_stock_level',
        'standard_cost',
        'average_cost',
        'last_cost',
        'valuation_method',
        'is_vatable',
        'vat_type',
        'is_active',
        'specifications',
        'notes',
    ];

    protected $casts = [
        'is_consumable' => 'boolean',
        'is_asset' => 'boolean',
        'reorder_level' => 'integer',
        'minimum_stock_level' => 'integer',
        'maximum_stock_level' => 'integer',
        'standard_cost' => 'decimal:2',
        'average_cost' => 'decimal:2',
        'last_cost' => 'decimal:2',
        'is_vatable' => 'boolean',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'item_name',
        'item_code',
        'quantity_on_hand',
        'reorder_level',
        'unit_cost',
        'store',
        'stock_status',
    ];

    /**
     * Relationships
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'inventory_item_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class, 'inventory_item_id');
    }

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
    }

    public function scopeConsumables($query)
    {
        return $query->where('is_consumable', true);
    }

    public function scopeAssets($query)
    {
        return $query->where('is_asset', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereHas('stockLevels', function ($q) {
            $q->whereRaw('quantity_on_hand <= inventory_items.reorder_point');
        });
    }

    public function scopeOutOfStock($query)
    {
        return $query->whereHas('stockLevels', function ($q) {
            $q->where('quantity_on_hand', 0);
        });
    }

    public function scopeInCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Business Logic
     */
    public function getTotalStockOnHand(): int
    {
        return $this->stockLevels()->sum('quantity_on_hand');
    }

    public function getTotalStockValue(): float
    {
        return $this->stockLevels()->sum('total_value');
    }

    public function isLowStock(): bool
    {
        return $this->getTotalStockOnHand() <= $this->reorder_level;
    }

    public function isOutOfStock(): bool
    {
        return $this->getTotalStockOnHand() == 0;
    }

    public function isOverStocked(): bool
    {
        return $this->getTotalStockOnHand() > $this->maximum_stock_level;
    }

    public function needsReorder(): bool
    {
        return $this->getTotalStockOnHand() <= $this->reorder_level;
    }

    public function getReorderQuantity(): int
    {
        $currentStock = $this->getTotalStockOnHand();
        $targetStock = $this->maximum_stock_level;

        if ($currentStock >= $targetStock) {
            return 0;
        }

        return $targetStock - $currentStock;
    }

    /**
     * Formatted Attributes
     */
    public function getStockStatusColorAttribute(): string
    {
        return match ($this->stock_status) {
            'out_of_stock' => 'red',
            'low_stock' => 'orange',
            'overstocked' => 'yellow',
            'adequate' => 'green',
            default => 'gray',
        };
    }

    public function getItemTypeAttribute(): string
    {
        if ($this->is_asset) {
            return 'Asset';
        } elseif ($this->is_consumable) {
            return 'Consumable';
        } else {
            return 'General';
        }
    }

    public function getFormattedAverageCostAttribute(): string
    {
        return 'KES ' . number_format($this->average_cost, 2);
    }

    /**
     * Accessors for view compatibility
     */
    public function getItemNameAttribute(): string
    {
        return $this->name ?? '';
    }

    public function getItemCodeAttribute(): ?string
    {
        return $this->item_code ?? null;
    }

    public function getQuantityOnHandAttribute(): int
    {
        return (int) $this->getTotalStockOnHand();
    }

    public function getReorderLevelAttribute(): ?int
    {
        return $this->reorder_point ?? null;
    }

    public function getUnitCostAttribute(): ?float
    {
        return $this->standard_cost ?? null;
    }

    public function getStoreAttribute()
    {
        return $this->stockLevels()->first()?->store;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'out_of_stock';
        } elseif ($this->isLowStock()) {
            return 'low_stock';
        } elseif ($this->isOverStocked()) {
            return 'overstocked';
        } else {
            return 'adequate';
        }
    }

    /**
     * Get stock status for views
     */
    public function getStockStatus(): string
    {
        return $this->stock_status;
    }
}