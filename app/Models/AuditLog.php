<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * AuditLog Model
 * 
 * Represents audit trail entries for all auditable models
 * 
 * @property int $id
 * @property int $user_id
 * @property string $user_name
 * @property string $user_email
 * @property string $action
 * @property string $auditable_type
 * @property int $auditable_id
 * @property array $old_values
 * @property array $new_values
 * @property string $justification
 * @property string $ip_address
 * @property string $user_agent
 * @property string $url
 * @property array $metadata
 * @property \Carbon\Carbon $created_at
 */
class AuditLog extends Model
{
    public $timestamps = false;

    /**
     * Enforce immutability: audit records must never be modified or deleted.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Audit log records are immutable and cannot be modified.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Audit log records are immutable and cannot be deleted.');
        });
    }

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'justification',
        'ip_address',
        'user_agent',
        'url',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
