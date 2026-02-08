<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WHTCertificate extends Model
{
    use HasFactory;

    protected $table = 'wht_certificates';

    protected $fillable = [
        'certificate_number',
        'payment_id',
        'supplier_id',
        'supplier_kra_pin',
        'financial_year',
        'payment_date',
        'gross_amount',
        'wht_rate',
        'wht_amount',
        'wht_type',
        'currency',
        'generated_by',
        'generated_at',
        'status',
        'cancelled_at',
        'cancellation_reason',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'gross_amount' => 'decimal:2',
        'wht_rate' => 'decimal:2',
        'wht_amount' => 'decimal:2',
        'generated_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Suppliers\Models\Supplier::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'generated_by');
    }

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeForFinancialYear($query, string $financialYear)
    {
        return $query->where('financial_year', $financialYear);
    }

    /**
     * Status Helpers
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Formatted Attributes
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'cancelled' => 'Cancelled',
            'replaced' => 'Replaced',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'green',
            'cancelled' => 'red',
            'replaced' => 'yellow',
            default => 'gray',
        };
    }

    public function getWHTTypeLabelAttribute(): string
    {
        return match ($this->wht_type) {
            'services' => 'Professional/Technical Services (5%)',
            'professional_fees' => 'Professional Fees (5%)',
            'management_fees' => 'Management/Consultancy Fees (2%)',
            'transport' => 'Transport Services (2%)',
            'interest' => 'Interest (15%)',
            'dividends' => 'Dividends (5%)',
            'rent' => 'Rent (10%)',
            default => ucwords(str_replace('_', ' ', $this->wht_type ?? 'N/A')),
        };
    }

    public function getFormattedGrossAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->gross_amount, 2);
    }

    public function getFormattedWHTAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->wht_amount, 2);
    }
}
