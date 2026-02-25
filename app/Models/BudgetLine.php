<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_code',
        'description',
        'fiscal_year',
        'department_id',
        'cost_center_id',
        'category',
        'allocated_amount',
        'available_amount',
        'committed_amount',
        'spent_amount',
        'is_active',
        'notes',
        'status',
        'rejection_reason',
        'submitted_by',
        'approved_by',
        'submitted_at',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'status' => 'string',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function requisitions(): HasMany
    {
        return $this->hasMany(\App\Modules\Requisitions\Models\Requisition::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BudgetTransaction::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(BudgetApproval::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForFiscalYear($query, string $fiscalYear)
    {
        return $query->where('fiscal_year', $fiscalYear);
    }

    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeWithUtilization($query)
    {
        return $query->selectRaw(
            'budget_lines.*, 
            (spent_amount / allocated_amount * 100) as utilization_percentage,
            (allocated_amount - committed_amount - spent_amount) as available_amount'
        );
    }

    /**
     * Helper Methods
     */
    public function getAvailableAmountAttribute(): float
    {
        return $this->allocated_amount - $this->committed_amount - $this->spent_amount;
    }

    public function getCodeAttribute(): ?string
    {
        return $this->budget_code;
    }

    public function getBudgetCategoryAttribute(): ?string
    {
        return $this->category;
    }

    public function getUtilizationPercentageAttribute(): float
    {
        if ($this->allocated_amount == 0) {
            return 0;
        }
        return (($this->committed_amount + $this->spent_amount) / $this->allocated_amount) * 100;
    }

    public function getSpentPercentageAttribute(): float
    {
        if ($this->allocated_amount == 0) {
            return 0;
        }
        return ($this->spent_amount / $this->allocated_amount) * 100;
    }

    public function hasAvailableFunds(float $amount): bool
    {
        return $this->getAvailableAmountAttribute() >= $amount;
    }

    public function isOverUtilized(): bool
    {
        return $this->getUtilizationPercentageAttribute() > 100;
    }

    public function isNearLimit(float $threshold = 90): bool
    {
        return $this->getUtilizationPercentageAttribute() >= $threshold;
    }

    /**
     * Budget Operations
     */
    public function commit(float $amount, string $reference, ?string $description = null): void
    {
        if (!$this->hasAvailableFunds($amount)) {
            throw new \Exception("Insufficient budget available. Required: {$amount}, Available: {$this->available_amount}");
        }

        $this->increment('committed_amount', $amount);

        // Log transaction
        $this->transactions()->create([
            'transaction_type' => 'commitment',
            'amount' => $amount,
            'reference_type' => get_class($reference),
            'reference_id' => is_object($reference) ? $reference->id : null,
            'description' => $description,
            'balance_after' => $this->fresh()->available_amount,
        ]);
    }

    public function uncommit(float $amount, string $reference, ?string $description = null): void
    {
        $this->decrement('committed_amount', $amount);

        $this->transactions()->create([
            'transaction_type' => 'uncommitment',
            'amount' => $amount,
            'reference_type' => get_class($reference),
            'reference_id' => is_object($reference) ? $reference->id : null,
            'description' => $description,
            'balance_after' => $this->fresh()->available_amount,
        ]);
    }

    public function spend(float $amount, string $reference, ?string $description = null): void
    {
        $this->increment('spent_amount', $amount);

        $this->transactions()->create([
            'transaction_type' => 'expenditure',
            'amount' => $amount,
            'reference_type' => get_class($reference),
            'reference_id' => is_object($reference) ? $reference->id : null,
            'description' => $description,
            'balance_after' => $this->fresh()->available_amount,
        ]);
    }

    /**
     * Formatted Attributes
     */
    public function getUtilizationStatusAttribute(): string
    {
        $percentage = $this->utilization_percentage;

        if ($percentage >= 100) {
            return 'Exceeded';
        } elseif ($percentage >= 90) {
            return 'Critical';
        } elseif ($percentage >= 75) {
            return 'Warning';
        } elseif ($percentage >= 50) {
            return 'Moderate';
        } else {
            return 'Low';
        }
    }

    public function getUtilizationColorAttribute(): string
    {
        return match ($this->utilization_status) {
            'Exceeded' => 'red',
            'Critical' => 'orange',
            'Warning' => 'yellow',
            'Moderate' => 'blue',
            'Low' => 'green',
            default => 'gray',
        };
    }
}
