<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class PaymentGatewayRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway_provider',
        'user_id',
        'role_type',
        'permissions',
        'is_active',
        'activated_at',
        'deactivated_at',
        'assigned_by',
        'assigned_at',
        'notes',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'assigned_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Check if user has specific gateway role
     */
    public static function userHasRole(int $userId, string $provider, string $roleType): bool
    {
        return static::where('user_id', $userId)
            ->where('gateway_provider', $provider)
            ->where('role_type', $roleType)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if user has permission for action
     */
    public static function userHasPermission(int $userId, string $provider, string $permission): bool
    {
        $roles = static::where('user_id', $userId)
            ->where('gateway_provider', $provider)
            ->where('is_active', true)
            ->get();

        foreach ($roles as $role) {
            if (is_array($role->permissions) && in_array($permission, $role->permissions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get user's active roles for gateway
     */
    public static function getUserRoles(int $userId, string $provider): array
    {
        return static::where('user_id', $userId)
            ->where('gateway_provider', $provider)
            ->where('is_active', true)
            ->pluck('role_type')
            ->toArray();
    }

    /**
     * Check if role allows action
     */
    public function canPerform(string $action): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $rolePermissions = [
            'initiator' => ['initiate_payment', 'cancel_payment'],
            'approver' => ['approve_payment', 'reject_payment'],
            'processor' => ['process_payment', 'retry_payment', 'bulk_payment'],
            'reconciler' => ['reconcile_payment', 'view_reports'],
            'admin' => ['all'], // Full access
        ];

        if ($this->role_type === 'admin') {
            return true;
        }

        return in_array($action, $rolePermissions[$this->role_type] ?? []);
    }
}
