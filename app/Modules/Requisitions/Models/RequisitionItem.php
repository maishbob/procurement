<?php

namespace App\Modules\Requisitions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'requisition_id',
        'catalog_item_id',
        'line_number',
        'description',
        'specifications',
        'quantity',
        'unit_of_measure',
        'estimated_unit_price',
        'estimated_total_price',
        'is_vatable',
        'vat_type',
        'subject_to_wht',
        'wht_type',
        'budget_line_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'estimated_unit_price' => 'decimal:2',
        'estimated_total_price' => 'decimal:2',
        'is_vatable' => 'boolean',
        'subject_to_wht' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BudgetLine::class);
    }

    /**
     * Formatted Attributes
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return 'KES ' . number_format($this->estimated_unit_price, 2);
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return 'KES ' . number_format($this->estimated_total_price, 2);
    }

    public function getVatStatusAttribute(): string
    {
        if (!$this->is_vatable) {
            return 'Exempt';
        }

        return match ($this->vat_type) {
            'vatable' => 'Standard 16%',
            'zero_rated' => 'Zero Rated',
            'exempt' => 'Exempt',
            default => 'Taxable',
        };
    }
}
