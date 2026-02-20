<?php

namespace App\Modules\Suppliers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class SupplierPerformanceReview extends Model
{
    protected $fillable = [
        'supplier_id',
        'reviewed_by',
        'review_period',
        'delivery_score',
        'quality_score',
        'compliance_score',
        'overall_score',
        'comments',
        'action_required',
        'action_details',
    ];

    protected $casts = [
        'delivery_score'   => 'decimal:2',
        'quality_score'    => 'decimal:2',
        'compliance_score' => 'decimal:2',
        'overall_score'    => 'decimal:2',
        'action_required'  => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
