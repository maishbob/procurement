<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'head_of_department_id',
        'parent_department_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function hod(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_of_department_id');
    }

    public function parentDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_department_id');
    }

    public function subDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_department_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function requisitions(): HasMany
    {
        return $this->hasMany(\App\Modules\Requisitions\Models\Requisition::class);
    }

    public function budgetLines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_department_id');
    }

    public function scopeWithBudgetSummary($query)
    {
        return $query->withSum('budgetLines', 'allocated_amount')
            ->withSum('budgetLines', 'spent_amount');
    }

    /**
     * Helper Methods
     */
    public function isSubDepartmentOf(int $departmentId): bool
    {
        if ($this->parent_department_id === $departmentId) {
            return true;
        }

        if ($this->parentDepartment) {
            return $this->parentDepartment->isSubDepartmentOf($departmentId);
        }

        return false;
    }

    /**
     * Formatted Attributes
     */
    public function getFullNameAttribute(): string
    {
        if ($this->parentDepartment) {
            return $this->parentDepartment->full_name . ' > ' . $this->name;
        }
        return $this->name;
    }

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
}
