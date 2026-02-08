<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_number',
        'supplier_id',
        'payment_date',
        'payment_method',
        'status',
        'currency',
        'exchange_rate',
        'gross_amount',
        'wht_amount',
        'wht_rate',
        'net_amount',
        'gross_amount_base',
        'wht_amount_base',
        'net_amount_base',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'reference_number',
        'cheque_number',
        'transaction_id',
        'prepared_by',
        'verified_by',
        'approved_by',
        'processed_by',
        'prepared_at',
        'verified_at',
        'approved_at',
        'processed_at',
        'wht_certificate_generated',
        'wht_certificate_number',
        'wht_certificate_generated_at',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'exchange_rate' => 'decimal:4',
        'gross_amount' => 'decimal:2',
        'wht_amount' => 'decimal:2',
        'wht_rate' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'gross_amount_base' => 'decimal:2',
        'wht_amount_base' => 'decimal:2',
        'net_amount_base' => 'decimal:2',
        'prepared_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'wht_certificate_generated' => 'boolean',
        'wht_certificate_generated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Suppliers\Models\Supplier::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'prepared_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(SupplierInvoice::class, 'payment_invoices')
            ->withPivot('amount_allocated')
            ->withTimestamps();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PaymentApproval::class);
    }

    public function whtCertificate(): HasMany
    {
        return $this->hasMany(WHTCertificate::class);
    }

    /**
     * Query Scopes
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending_verification', 'pending_approval']);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeWithWHT($query)
    {
        return $query->where('wht_amount', '>', 0);
    }

    public function scopeWHTCertificatePending($query)
    {
        return $query->where('wht_amount', '>', 0)
            ->where('wht_certificate_generated', false)
            ->whereIn('status', ['processed', 'completed']);
    }

    /**
     * Status Helpers
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPendingVerification(): bool
    {
        return $this->status === 'pending_verification';
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function canBeVerified(): bool
    {
        return $this->status === 'pending_verification';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function canBeProcessed(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Business Logic
     */
    public function hasWHT(): bool
    {
        return $this->wht_amount > 0;
    }

    public function needsWHTCertificate(): bool
    {
        return $this->hasWHT() && !$this->wht_certificate_generated;
    }

    public function getWHTPercentageAttribute(): float
    {
        if ($this->gross_amount == 0) {
            return 0;
        }

        return ($this->wht_amount / $this->gross_amount) * 100;
    }

    /**
     * Formatted Attributes
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'pending_verification' => 'Pending Verification',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'processed' => 'Processed',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            'on_hold' => 'On Hold',
            'cancelled' => 'Cancelled',
            'failed' => 'Failed',
            'reversed' => 'Reversed',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'pending_verification' => 'yellow',
            'pending_approval' => 'orange',
            'approved' => 'blue',
            'processed' => 'purple',
            'completed' => 'green',
            'rejected' => 'red',
            'on_hold' => 'orange',
            'cancelled' => 'red',
            'failed' => 'red',
            'reversed' => 'red',
            default => 'gray',
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            'mobile_money' => 'Mobile Money',
            'cash' => 'Cash',
            'eft' => 'Electronic Funds Transfer',
            'rtgs' => 'RTGS',
            default => ucfirst($this->payment_method ?? 'N/A'),
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

    public function getFormattedNetAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->net_amount, 2);
    }

    public function getWHTSummaryAttribute(): string
    {
        if (!$this->hasWHT()) {
            return 'No WHT';
        }

        return "WHT {$this->wht_rate}%: {$this->formatted_wht_amount}";
    }
}
