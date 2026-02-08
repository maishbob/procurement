<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransaction extends Model
{
    protected $fillable = [
        'transaction_number',
        'inventory_item_id',
        'store_id',
        'transaction_type',
        'quantity',
        'unit_of_measure',
        'unit_cost',
        'total_value',
        'reference_type',
        'reference_id',
        'reference_number',
        'to_store_id',
        'from_store_id',
        'issued_to_user_id',
        'issued_to_department_id',
        'expected_return_date',
        'requires_approval',
        'approved_by',
        'approved_at',
        'batch_number',
        'serial_number',
        'expiry_date',
        'status',
        'notes',
        'justification',
        'created_by',
        'transaction_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
        'expected_return_date' => 'date',
        'expiry_date' => 'date',
        'transaction_date' => 'datetime',
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
        return $this->belongsTo(Store::class);
    }

    public function toStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function fromStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function issuedToUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'issued_to_user_id');
    }

    public function issuedToDepartment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Department::class, 'issued_to_department_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }
}
