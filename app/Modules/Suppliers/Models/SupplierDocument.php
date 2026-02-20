<?php

namespace App\Modules\Suppliers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class SupplierDocument extends Model
{
    protected $fillable = [
        'supplier_id',
        'document_type',
        'file_path',
        'file_name',
        'expiry_date',
        'is_required',
        'verified',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'expiry_date'  => 'date',
        'verified_at'  => 'datetime',
        'is_required'  => 'boolean',
        'verified'     => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date->isFuture()
            && $this->expiry_date->lte(now()->addDays($days));
    }
}
