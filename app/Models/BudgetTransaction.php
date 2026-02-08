<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_line_id',
        'transaction_type',
        'amount',
        'reference_type',
        'reference_id',
        'description',
        'balance_after',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Formatted Attributes
     */
    public function getTransactionTypeLabelAttribute(): string
    {
        return match ($this->transaction_type) {
            'commitment' => 'Budget Commitment',
            'uncommitment' => 'Budget Uncommitment',
            'expenditure' => 'Expenditure',
            'allocation' => 'Budget Allocation',
            'adjustment' => 'Budget Adjustment',
            'transfer' => 'Budget Transfer',
            default => ucfirst($this->transaction_type),
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'KES ' . number_format($this->amount, 2);
    }
}
