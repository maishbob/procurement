<?php

namespace App\Modules\Requisitions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'requisition_id',
        'approval_level',
        'approver_id',
        'status',
        'comments',
        'responded_at',
        'ip_address',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approver_id');
    }

    /**
     * Status Helpers
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Formatted Attributes
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Awaiting Response',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'delegated' => 'Delegated',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'delegated' => 'blue',
            default => 'gray',
        };
    }

    public function getLevelLabelAttribute(): string
    {
        return match ($this->approval_level) {
            'hod' => 'Head of Department',
            'budget_owner' => 'Budget Owner',
            'principal' => 'Principal',
            'deputy_principal' => 'Deputy Principal',
            'finance_manager' => 'Finance Manager',
            'board' => 'Board of Governors',
            default => ucwords(str_replace('_', ' ', $this->approval_level)),
        };
    }
}
