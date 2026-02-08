<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'phone',
        'password',
        'department_id',
        'job_title',
        'is_active',
        'last_login_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_hod' => 'boolean',
        'is_budget_owner' => 'boolean',
        'can_approve_requisitions' => 'boolean',
        'can_approve_purchase_orders' => 'boolean',
        'can_approve_payments' => 'boolean',
        'max_approval_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requestedRequisitions(): HasMany
    {
        return $this->hasMany(\App\Modules\Requisitions\Models\Requisition::class, 'requested_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(\App\Modules\Requisitions\Models\RequisitionApproval::class, 'approver_id');
    }

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
    }

    public function scopeInDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeApprovers($query)
    {
        return $query->where(function ($q) {
            $q->where('can_approve_requisitions', true)
                ->orWhere('can_approve_purchase_orders', true)
                ->orWhere('can_approve_payments', true);
        });
    }

    public function scopeHODs($query)
    {
        return $query->where('is_hod', true);
    }

    public function scopeBudgetOwners($query)
    {
        return $query->where('is_budget_owner', true);
    }

    /**
     * Helper Methods
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function canApproveAmount(float $amount, string $type = 'requisition'): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $canApproveField = match ($type) {
            'requisition' => 'can_approve_requisitions',
            'purchase_order' => 'can_approve_purchase_orders',
            'payment' => 'can_approve_payments',
            default => null,
        };

        if (!$canApproveField || !$this->{$canApproveField}) {
            return false;
        }

        return $this->max_approval_amount === null || $amount <= $this->max_approval_amount;
    }

    public function isHODOf(int $departmentId): bool
    {
        return $this->is_hod && $this->department_id === $departmentId;
    }

    public function isBudgetOwner(): bool
    {
        return $this->is_budget_owner;
    }

    public function updateLoginInfo(): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
    }

    /**
     * Authorization Helpers
     */
    public function canPerform(string $action, string $model): bool
    {
        return $this->hasPermissionTo("{$model}.{$action}");
    }

    public function hasPermission(string $permission): bool
    {
        return $this->hasPermissionTo($permission);
    }

    public function isSuper(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isPrincipal(): bool
    {
        return $this->hasRole('principal');
    }

    public function isFinanceManager(): bool
    {
        return $this->hasRole('finance-manager');
    }

    public function isProcurementOfficer(): bool
    {
        return $this->hasRole('procurement-officer');
    }

    public function isStoresManager(): bool
    {
        return $this->hasRole('stores-manager');
    }

    /**
     * Formatted Attributes
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->deleted_at) {
            return 'Deleted';
        }
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->deleted_at) {
            return 'red';
        }
        return $this->is_active ? 'green' : 'gray';
    }

    public function getRolesListAttribute(): string
    {
        return $this->roles->pluck('name')->join(', ');
    }
}
