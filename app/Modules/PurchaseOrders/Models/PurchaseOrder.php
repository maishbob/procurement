<?php

namespace App\Modules\PurchaseOrders\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number',
        'requisition_id',
        'supplier_id',
        'department_id',
        'ordered_by',
        'approved_by',
        'status',
        'po_date',
        'delivery_date',
        'delivery_location',
        'delivery_instructions',
        'currency',
        'exchange_rate',
        'subtotal',
        'vat_amount',
        'total_amount',
        'total_amount_base',
        'payment_terms',
        'terms_and_conditions',
        'special_instructions',
        'is_partial_delivery_allowed',
        'receiving_status',
        'received_quantity_percentage',
        'approved_at',
        'issued_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'notes',
    ];

    protected $casts = [
        'po_date' => 'date',
        'delivery_date' => 'date',
        'exchange_rate' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_amount_base' => 'decimal:2',
        'is_partial_delivery_allowed' => 'boolean',
        'received_quantity_percentage' => 'decimal:2',
        'approved_at' => 'datetime',
        'issued_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Requisitions\Models\Requisition::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Suppliers\Models\Supplier::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'ordered_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceivedNotes(): HasMany
    {
        return $this->hasMany(\App\Modules\GRN\Models\GoodsReceivedNote::class, 'purchase_order_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(\App\Modules\Finance\Models\SupplierInvoice::class, 'purchase_order_id');
    }

    /**
     * Query Scopes
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending_approval']);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeIssued($query)
    {
        return $query->where('status', 'issued');
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['partially_received', 'in_transit']);
    }

    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'issued')
            ->where('delivery_date', '<', Carbon::today());
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('status', 'issued')
            ->whereBetween('delivery_date', [
                Carbon::today(),
                Carbon::today()->addDays($days)
            ]);
    }

    /**
     * Status Helpers
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }

    public function isPartiallyReceived(): bool
    {
        return $this->status === 'partially_received';
    }

    public function isFullyReceived(): bool
    {
        return $this->status === 'fully_received';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft']);
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function canBeIssued(): bool
    {
        return $this->status === 'approved';
    }

    public function canReceiveGoods(): bool
    {
        return in_array($this->status, ['issued', 'partially_received']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'pending_approval', 'approved', 'issued']);
    }

    public function isOverdue(): bool
    {
        return $this->isIssued() && $this->delivery_date < Carbon::today();
    }

    public function isDueSoon(int $days = 7): bool
    {
        if (!$this->isIssued()) {
            return false;
        }

        return $this->delivery_date >= Carbon::today()
            && $this->delivery_date <= Carbon::today()->addDays($days);
    }

    /**
     * Business Logic
     */
    public function getTotalReceivedQuantity(): int
    {
        return $this->goodsReceivedNotes()
            ->whereIn('status', ['approved', 'posted_to_inventory'])
            ->sum('total_quantity_received');
    }

    public function getTotalOrderedQuantity(): int
    {
        return $this->items()->sum('quantity');
    }

    public function updateReceivingStatus(): void
    {
        $totalOrdered = $this->getTotalOrderedQuantity();
        $totalReceived = $this->getTotalReceivedQuantity();

        if ($totalOrdered == 0) {
            $percentage = 0;
        } else {
            $percentage = ($totalReceived / $totalOrdered) * 100;
        }

        $this->received_quantity_percentage = $percentage;

        if ($percentage >= 100) {
            $this->receiving_status = 'fully_received';
        } elseif ($percentage > 0) {
            $this->receiving_status = 'partially_received';
        } else {
            $this->receiving_status = 'pending';
        }

        $this->save();
    }

    public function hasOutstandingInvoices(): bool
    {
        return $this->invoices()->whereNotIn('status', ['paid', 'cancelled'])->exists();
    }

    /**
     * Formatted Attributes
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'issued' => 'Issued to Supplier',
            'acknowledged' => 'Acknowledged by Supplier',
            'in_transit' => 'In Transit',
            'partially_received' => 'Partially Received',
            'fully_received' => 'Fully Received',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'on_hold' => 'On Hold',
            'disputed' => 'Disputed',
            'returned' => 'Returned',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'pending_approval' => 'yellow',
            'approved' => 'blue',
            'issued' => 'purple',
            'acknowledged' => 'indigo',
            'in_transit' => 'teal',
            'partially_received' => 'orange',
            'fully_received' => 'green',
            'completed' => 'green',
            'cancelled' => 'red',
            'on_hold' => 'orange',
            'disputed' => 'red',
            'returned' => 'red',
            default => 'gray',
        };
    }

    public function getReceivingStatusLabelAttribute(): string
    {
        return match ($this->receiving_status) {
            'pending' => 'Not Started',
            'partially_received' => 'In Progress',
            'fully_received' => 'Complete',
            default => ucfirst($this->receiving_status ?? 'N/A'),
        };
    }

    public function getFormattedTotalAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->total_amount, 2);
    }

    public function getFormattedTotalBaseAttribute(): string
    {
        return 'KES ' . number_format($this->total_amount_base, 2);
    }

    public function getDaysUntilDeliveryAttribute(): ?int
    {
        if (!$this->delivery_date) {
            return null;
        }

        return Carbon::today()->diffInDays($this->delivery_date, false);
    }
}
