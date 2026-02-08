<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BidEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_bid_id',
        'evaluator_id',
        'scores',
        'total_score',
        'comments',
        'strengths',
        'weaknesses',
        'recommendation',
        'recommendation_notes',
        'evaluated_at',
    ];

    protected $casts = [
        'scores' => 'array',
        'total_score' => 'decimal:2',
        'evaluated_at' => 'datetime',
    ];

    public function bid()
    {
        return $this->belongsTo(SupplierBid::class, 'supplier_bid_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
