<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ConflictOfInterestDeclaration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'declarable_type',
        'declarable_id',
        'has_conflict',
        'conflict_details',
        'declared_at',
    ];

    protected $casts = [
        'has_conflict' => 'boolean',
        'declared_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function declarable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if user has declared conflict for a given entity
     */
    public static function hasConflict(int $userId, string $entityType, int $entityId): bool
    {
        return static::where('user_id', $userId)
            ->where('declarable_type', $entityType)
            ->where('declarable_id', $entityId)
            ->where('has_conflict', true)
            ->exists();
    }

    /**
     * Get active conflicts for user
     */
    public static function getActiveConflicts(int $userId): array
    {
        return static::where('user_id', $userId)
            ->where('has_conflict', true)
            ->get()
            ->map(fn($declaration) => [
                'entity_type' => $declaration->declarable_type,
                'entity_id' => $declaration->declarable_id,
                'details' => $declaration->conflict_details,
                'declared_at' => $declaration->declared_at,
            ])
            ->toArray();
    }
}
