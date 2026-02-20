<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'report_type',
        'frequency',        // daily, weekly, monthly, quarterly
        'recipients',       // JSON array of email addresses
        'parameters',       // JSON report parameters/filters
        'is_active',
        'last_run_at',
        'next_run_at',
        'created_by',
    ];

    protected $casts = [
        'recipients'   => 'array',
        'parameters'   => 'array',
        'is_active'    => 'boolean',
        'last_run_at'  => 'datetime',
        'next_run_at'  => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('next_run_at', '<=', now())
                     ->where('is_active', true);
    }
}
