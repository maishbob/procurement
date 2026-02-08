<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierBid extends Model
{
    use HasFactory;

    protected $fillable = [
        'bid_number',
        'procurement_process_id',
        'supplier_id',
        'submitted_at',
        'is_late',
        'total_amount',
        'currency',
        'exchange_rate',
        'total_amount_base',
        'delivery_days',
        'validity_days',
        'terms_and_conditions',
        'status',
        'evaluation_score',
        'evaluation_scores',
        'evaluation_comments',
        'disqualification_reason',
        'coi_declared',
        'coi_details',
        'attachments',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bid) {
            if (empty($bid->bid_number)) {
                $bid->bid_number = static::generateBidNumber();
            }
            if (empty($bid->submitted_at)) {
                $bid->submitted_at = now();
            }
        });
    }

    protected static function generateBidNumber(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        
        return sprintf('BID-%s-%05d', $year, $count);
    }

    protected $casts = [
        'submitted_at' => 'datetime',
        'is_late' => 'boolean',
        'total_amount' => 'decimal:2',
        'total_amount_base' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'evaluation_score' => 'decimal:2',
        'evaluation_scores' => 'array',
        'coi_declared' => 'boolean',
        'attachments' => 'array',
    ];

    public function procurementProcess()
    {
        return $this->belongsTo(ProcurementProcess::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(SupplierBidItem::class);
    }

    public function evaluations()
    {
        return $this->hasMany(BidEvaluation::class);
    }

    public function evaluation() 
    {
        return $this->hasOne(BidEvaluation::class)->latest();
    }
}
