<?php

namespace App\Modules\Suppliers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierCategory extends Model
{
    protected $fillable = [
        'supplier_id',
        'category_name',
        'approved_at',
        'is_active',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'is_active'   => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
