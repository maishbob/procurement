<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualProcurementPlanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'annual_procurement_plan_id',
        'category',
        'description',
        'planned_quarter',
        'estimated_value',
        'sourcing_method',
    ];

    public function plan()
    {
        return $this->belongsTo(AnnualProcurementPlan::class, 'annual_procurement_plan_id');
    }
}
