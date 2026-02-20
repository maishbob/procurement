<?php

namespace App\Modules\Quality\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Department;

class CapaAction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'capa_number',
        'type',
        'title',
        'description',
        'source',
        'source_reference',
        'source_entity_type',
        'source_entity_id',
        'problem_statement',
        'root_cause_analysis',
        'immediate_action_taken',
        'proposed_action',
        'implementation_plan',
        'target_completion_date',
        'actual_completion_date',
        'raised_by',
        'assigned_to',
        'department_id',
        'priority',
        'status',
        'approved_by',
        'approved_at',
        'approval_comments',
        'verified_by',
        'verified_at',
        'verification_comments',
        'verification_passed',
        'effectiveness_review_date',
        'reviewed_by',
        'reviewed_at',
        'effectiveness_review_comments',
        'effectiveness_rating',
        'estimated_cost',
        'actual_cost',
        'attachments',
        'lessons_learned',
    ];

    protected $casts = [
        'target_completion_date' => 'date',
        'actual_completion_date' => 'date',
        'effectiveness_review_date' => 'date',
        'approved_at' => 'datetime',
        'verified_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'verification_passed' => 'boolean',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'attachments' => 'array',
    ];

    /**
     * Relationships
     */
    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function sourceEntity(): MorphTo
    {
        return $this->morphTo();
    }

    public function updates(): HasMany
    {
        return $this->hasMany(CapaActionUpdate::class)->orderBy('created_at', 'desc');
    }

    /**
     * Status helpers
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'in_progress', 'pending_verification', 'verified', 'closed']);
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function canBeVerified(): bool
    {
        return $this->status === 'pending_verification';
    }

    public function canBeClosed(): bool
    {
        return $this->status === 'verified';
    }

    /**
     * Check if overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->target_completion_date || $this->isClosed()) {
            return false;
        }
        return now()->gt($this->target_completion_date);
    }

    /**
     * Calculate days until/past due date
     */
    public function daysUntilDue(): ?int
    {
        if (!$this->target_completion_date) {
            return null;
        }
        return now()->diffInDays($this->target_completion_date, false);
    }

    /**
     * Get progress percentage from latest update
     */
    public function getProgressPercentage(): float
    {
        $latestUpdate = $this->updates()->first();
        return $latestUpdate ? $latestUpdate->progress_percentage : 0;
    }

    /**
     * Type helpers
     */
    public function isCorrective(): bool
    {
        return $this->type === 'corrective';
    }

    public function isPreventive(): bool
    {
        return $this->type === 'preventive';
    }

    /**
     * Priority helpers
     */
    public function isCritical(): bool
    {
        return $this->priority === 'critical';
    }

    public function isHighPriority(): bool
    {
        return in_array($this->priority, ['critical', 'high']);
    }
}
