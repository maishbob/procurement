<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcurementProcess extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'process_number',
        'requisition_id',
        'type',
        'title',
        'description',
        'issue_date',
        'submission_deadline',
        'evaluation_date',
        'award_date',
        'terms_and_conditions',
        'status',
        'evaluation_criteria',
        'evaluation_team_lead',
        'evaluation_team_members',
        'evaluation_notes',
        'recommended_supplier_id',
        'award_justification',
        'awarded_supplier_id',
        'awarded_by',
        'awarded_at',
        'estimated_total',
        'awarded_amount',
        'currency',
        'attachments',
        'created_by',
        'updated_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($process) {
            if (empty($process->process_number)) {
                $process->process_number = static::generateProcessNumber($process->type);
            }
        });
    }

    protected static function generateProcessNumber(string $type): string
    {
        $prefix = match(strtolower($type)) {
            'rfq' => 'RFQ',
            'rfp' => 'RFP',
            'tender' => 'TND',
            default => 'PRO',
        };

        $year = date('Y');
        $count = static::whereYear('created_at', $year)
            ->where('type', strtolower($type))
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $year, $count);
    }

    protected $casts = [
        'issue_date' => 'date',
        'submission_deadline' => 'datetime',
        'evaluation_date' => 'date',
        'award_date' => 'date',
        'awarded_at' => 'datetime',
        'evaluation_criteria' => 'array',
        'evaluation_team_members' => 'array',
        'attachments' => 'array',
        'estimated_total' => 'decimal:2',
        'awarded_amount' => 'decimal:2',
    ];

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function bids()
    {
        return $this->hasMany(SupplierBid::class);
    }

    public function invitedSuppliers()
    {
        return $this->belongsToMany(Supplier::class, 'procurement_invited_suppliers')
            ->withPivot('invited_at', 'acknowledged_at', 'submitted_bid', 'bid_submitted_at', 'status')
            ->withTimestamps();
    }

    public function items()
    {
        return $this->hasMany(ProcurementProcessItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function evaluations()
    {
        return $this->hasManyThrough(BidEvaluation::class, SupplierBid::class);
    }

    // Accessor for process_type to map to type column
    public function getProcessTypeAttribute()
    {
        $type = $this->type;
        if ($type === 'tender') {
            return 'Tender';
        }
        return strtoupper($type);
    }

    // Mutator for process_type to map to type column
    public function setProcessTypeAttribute($value)
    {
        $this->attributes['type'] = strtolower($value);
    }
}
