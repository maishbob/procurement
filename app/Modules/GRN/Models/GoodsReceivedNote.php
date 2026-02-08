<?php

namespace App\Modules\GRN\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class GoodsReceivedNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'grn_number',
        'purchase_order_id',
        'supplier_id',
        'received_by',
        'inspected_by',
        'approved_by',
        'status',
        'received_date',
        'inspection_date',
        'delivery_note_number',
        'vehicle_registration',
        'driver_name',
        'driver_phone',
        'total_quantity_ordered',
        'total_quantity_received',
        'total_quantity_accepted',
        'total_quantity_rejected',
        'has_discrepancies',
        'discrepancy_details',
        'quality_check_passed',
        'quality_check_notes',
        'inspection_notes',
        'posted_to_inventory',
        'posted_to_inventory_at',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'received_date' => 'datetime',
        'inspection_date' => 'datetime',
        'has_discrepancies' => 'boolean',
        'quality_check_passed' => 'boolean',
        'posted_to_inventory' => 'boolean',
        'posted_to_inventory_at' => 'datetime',
        'approved_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\PurchaseOrders\Models\PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Suppliers\Models\Supplier::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }

    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'inspected_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GRNItem::class, 'grn_id');
    }

    /**
     * Query Scopes
     */
    public function scopePendingInspection($query)
    {
        return $query->where('status', 'pending_inspection');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeNotPosted($query)
    {
        return $query->where('posted_to_inventory', false)
            ->where('status', 'approved');
    }

    public function scopeWithDiscrepancies($query)
    {
        return $query->where('has_discrepancies', true);
    }

    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Status Helpers
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPendingInspection(): bool
    {
        return $this->status === 'pending_inspection';
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPostedToInventory(): bool
    {
        return $this->posted_to_inventory;
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function canBeInspected(): bool
    {
        return $this->status === 'pending_inspection';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending_approval' && $this->quality_check_passed;
    }

    public function canBePosted(): bool
    {
        return $this->status === 'approved' && !$this->posted_to_inventory;
    }

    /**
     * Business Logic
     */
    public function getAcceptanceRateAttribute(): float
    {
        if ($this->total_quantity_received == 0) {
            return 0;
        }

        return ($this->total_quantity_accepted / $this->total_quantity_received) * 100;
    }

    public function getVarianceAttribute(): int
    {
        return $this->total_quantity_received - $this->total_quantity_ordered;
    }

    public function hasShortage(): bool
    {
        return $this->total_quantity_received < $this->total_quantity_ordered;
    }

    public function hasOverSupply(): bool
    {
        return $this->total_quantity_received > $this->total_quantity_ordered;
    }

    public function hasRejections(): bool
    {
        return $this->total_quantity_rejected > 0;
    }

    /**
     * Formatted Attributes
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'pending_inspection' => 'Pending Inspection',
            'under_inspection' => 'Under Inspection',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'posted_to_inventory' => 'Posted to Inventory',
            'rejected' => 'Rejected',
            'on_hold' => 'On Hold',
            'returned_to_supplier' => 'Returned to Supplier',
            'partially_accepted' => 'Partially Accepted',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'pending_inspection' => 'yellow',
            'under_inspection' => 'blue',
            'pending_approval' => 'orange',
            'approved' => 'green',
            'posted_to_inventory' => 'green',
            'rejected' => 'red',
            'on_hold' => 'orange',
            'returned_to_supplier' => 'red',
            'partially_accepted' => 'yellow',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function getQualityStatusLabelAttribute(): string
    {
        if ($this->quality_check_passed === null) {
            return 'Pending';
        }

        return $this->quality_check_passed ? 'Passed' : 'Failed';
    }

    public function getQualityStatusColorAttribute(): string
    {
        if ($this->quality_check_passed === null) {
            return 'gray';
        }

        return $this->quality_check_passed ? 'green' : 'red';
    }
}
