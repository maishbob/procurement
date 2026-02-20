<?php

namespace App\Modules\Requisitions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\User;
use App\Models\Department;
use App\Models\AuditLog;

/**
 * Requisition Model
 * 
 * Represents a purchase requisition with full workflow support
 * 
 * @property int $id
 * @property string $requisition_number
 * @property int $department_id
 * @property int $requested_by
 * @property string $title
 * @property string $description
 * @property string $justification
 * @property string $status
 * @property float $estimated_total
 * @property string $currency
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Requisition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'requisition_number',
        'department_id',
        'cost_center_id',
        'budget_line_id',
        'requested_by',
        'title',
        'description',
        'justification',
        'priority',
        'type',
        'required_by_date',
        'delivery_location',
        'estimated_total',
        'currency',
        'exchange_rate',
        'estimated_total_base',
        'status',
        'is_emergency',
        'emergency_justification',
        'is_single_source',
        'single_source_justification',
        'preferred_supplier_id',
        'attachments',
        'supporting_documents',
        'notes',
    ];

    protected $casts = [
        'required_by_date' => 'date',
        'estimated_total' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'estimated_total_base' => 'decimal:2',
        'is_emergency' => 'boolean',
        'is_single_source' => 'boolean',
        'requires_hod_approval' => 'boolean',
        'requires_principal_approval' => 'boolean',
        'requires_board_approval' => 'boolean',
        'requires_tender' => 'boolean',
        'attachments' => 'array',
        'supporting_documents' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(RequisitionApproval::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled', 'rejected']);
    }

    public function scopeAwaitingApproval($query)
    {
        return $query->whereIn('status', ['submitted', 'hod_review', 'budget_review']);
    }

    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByRequester($query, int $userId)
    {
        return $query->where('requested_by', $userId);
    }

    public function scopeEmergency($query)
    {
        return $query->where('is_emergency', true);
    }

    // Helpers

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft']);
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft' && $this->items()->count() > 0;
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['budget_approved', 'procurement_queue', 'sourcing', 'completed']);
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled', 'rejected']);
    }

    public function requiresHODApproval(): bool
    {
        return $this->requires_hod_approval;
    }

    public function requiresPrincipalApproval(): bool
    {
        return $this->requires_principal_approval;
    }

    public function requiresTender(): bool
    {
        return $this->requires_tender;
    }

    // Status helpers

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'hod_review' => 'HOD Review',
            'hod_approved' => 'HOD Approved',
            'budget_review' => 'Budget Review',
            'budget_approved' => 'Budget Approved',
            'procurement_queue' => 'In Procurement Queue',
            'sourcing' => 'Sourcing',
            'quoted' => 'Quoted',
            'evaluated' => 'Evaluated',
            'awarded' => 'Awarded',
            'po_created' => 'PO Created',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'submitted', 'hod_review', 'budget_review' => 'yellow',
            'hod_approved', 'budget_approved', 'procurement_queue' => 'blue',
            'sourcing', 'quoted', 'evaluated', 'awarded' => 'indigo',
            'po_created', 'completed' => 'green',
            'rejected', 'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => 'Normal',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'gray',
            'normal' => 'blue',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'blue',
        };
    }

    // Formatted values

    public function getFormattedEstimatedTotalAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->estimated_total, 2);
    }
}
