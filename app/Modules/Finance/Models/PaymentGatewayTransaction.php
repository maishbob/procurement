<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class PaymentGatewayTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway_provider',
        'payment_id',
        'gateway_transaction_id',
        'merchant_reference',
        'transaction_type',
        'transaction_status',
        'amount',
        'currency',
        'initiated_by',
        'initiated_at',
        'processed_by',
        'processed_at',
        'reconciled_by',
        'reconciled_at',
        'gateway_request',
        'gateway_response',
        'error_message',
        'retry_count',
        'last_retry_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'initiated_at' => 'datetime',
        'processed_at' => 'datetime',
        'reconciled_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'gateway_request' => 'array',
        'gateway_response' => 'array',
        'retry_count' => 'integer',
    ];

    /**
     * Relationships
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    /**
     * Status helpers
     */
    public function isPending(): bool
    {
        return in_array($this->transaction_status, ['initiated', 'pending', 'processing']);
    }

    public function isCompleted(): bool
    {
        return $this->transaction_status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->transaction_status === 'failed';
    }

    public function canRetry(): bool
    {
        return $this->isFailed() && $this->retry_count < 3;
    }

    /**
     * Segregation validation
     */
    public function validateSegregation(): bool
    {
        // Ensure initiator and processor are different
        if ($this->initiated_by && $this->processed_by && $this->initiated_by === $this->processed_by) {
            return false;
        }

        // Ensure processor and reconciler are different
        if ($this->processed_by && $this->reconciled_by && $this->processed_by === $this->reconciled_by) {
            return false;
        }

        return true;
    }
}
