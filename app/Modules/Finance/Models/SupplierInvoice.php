<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SupplierInvoice extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'supplier_invoices';
    use HasFactory, SoftDeletes;

    /**
     * Tell Laravel where to find the factory for this model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Modules\Finance\Models\SupplierInvoiceFactory::new();
    }
    use HasFactory, SoftDeletes;

    /**
     * Alias for GRN relationship for legacy/test compatibility
     */
    public function goodsReceivedNote(): BelongsTo
    {
        return $this->grn();
    }
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'supplier_invoice_number',
        'purchase_order_id',
        'grn_id',
        'supplier_id',
        'invoice_date',
        'due_date',
        'status',
        'currency',
        'exchange_rate',
        'subtotal',
        'vat_amount',
        'wht_amount',
        'total_amount',
        'amount_due',
        'amount_paid',
        'total_amount_base',
        'three_way_match_status',
        'three_way_match_passed',
        'three_way_match_details',
        'three_way_match_performed_by',
        'three_way_match_performed_at',
        'verified_by',
        'verified_at',
        'approved_by',
        'approved_at',
        'payment_reference',
        'etims_control_number',
        'etims_invoice_reference',
        'etims_qr_code',
        'etims_verified',
        'etims_verified_at',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'wht_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'total_amount_base' => 'decimal:2',
        'three_way_match_passed' => 'boolean',
        'three_way_match_details' => 'array',
        'three_way_match_performed_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'etims_verified' => 'boolean',
        'etims_verified_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\PurchaseOrders\Models\PurchaseOrder::class);
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\GRN\Models\GoodsReceivedNote::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Suppliers\Models\Supplier::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierInvoiceItem::class);
    }

    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'payment_invoices')
            ->withPivot('amount_allocated')
            ->withTimestamps();
    }

    /**
     * Query Scopes
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending_verification']);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved_for_payment');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('amount_due', '>', 0)
            ->where('status', '!=', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', Carbon::today())
            ->where('amount_due', '>', 0)
            ->where('status', '!=', 'paid');
    }

    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeThreeWayMatchPassed($query)
    {
        return $query->where('three_way_match_passed', true);
    }

    public function scopeThreeWayMatchFailed($query)
    {
        return $query->where('three_way_match_passed', false);
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

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isApprovedForPayment(): bool
    {
        return $this->status === 'approved_for_payment';
    }

    public function isPartiallyPaid(): bool
    {
        return $this->status === 'partially_paid';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->due_date < Carbon::today() && $this->amount_due > 0 && !$this->isPaid();
    }

    public function canBeVerified(): bool
    {
        return $this->status === 'pending_verification';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'verified' && $this->three_way_match_passed;
    }

    public function canBePaid(): bool
    {
        return $this->status === 'approved_for_payment' && $this->amount_due > 0;
    }

    /**
     * Business Logic
     */
    public function performThreeWayMatch(): bool
    {
        // This would be called from service layer
        // Compares PO + GRN + Invoice
        return true;
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return Carbon::today()->diffInDays($this->due_date, false);
    }

    public function getDaysOverdueAttribute(): ?int
    {
        if (!$this->isOverdue()) {
            return null;
        }

        return Carbon::today()->diffInDays($this->due_date, false);
    }

    public function getPaymentProgressPercentageAttribute(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }

        return ($this->amount_paid / $this->total_amount) * 100;
    }

    /**
     * Formatted Attributes
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'pending_verification' => 'Pending Verification',
            'verified' => 'Verified',
            'approved_for_payment' => 'Approved for Payment',
            'partially_paid' => 'Partially Paid',
            'paid' => 'Fully Paid',
            'rejected' => 'Rejected',
            'disputed' => 'Disputed',
            'on_hold' => 'On Hold',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'pending_verification' => 'yellow',
            'verified' => 'blue',
            'approved_for_payment' => 'purple',
            'partially_paid' => 'orange',
            'paid' => 'green',
            'rejected' => 'red',
            'disputed' => 'red',
            'on_hold' => 'orange',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function getThreeWayMatchStatusLabelAttribute(): string
    {
        return match ($this->three_way_match_status) {
            'pending' => 'Not Performed',
            'passed' => 'Passed',
            'failed' => 'Failed',
            'manual_override' => 'Manually Overridden',
            default => ucfirst($this->three_way_match_status ?? 'N/A'),
        };
    }

    public function getThreeWayMatchColorAttribute(): string
    {
        return match ($this->three_way_match_status) {
            'pending' => 'gray',
            'passed' => 'green',
            'failed' => 'red',
            'manual_override' => 'yellow',
            default => 'gray',
        };
    }

    public function getFormattedTotalAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->total_amount, 2);
    }

    public function getFormattedAmountDueAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->amount_due, 2);
    }
}
