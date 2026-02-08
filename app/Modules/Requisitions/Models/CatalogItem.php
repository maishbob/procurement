<?php

namespace App\Modules\Requisitions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'code',
        'name',
        'description',
        'unit_of_measure',
        'standard_specifications',
        'estimated_cost',
        'is_vatable',
        'vat_type',
        'subject_to_wht',
        'wht_type',
        'is_active',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'is_vatable' => 'boolean',
        'subject_to_wht' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}
