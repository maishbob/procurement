<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualProcurementPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'fiscal_year',
        'description',
        'status',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'created_by',
        'updated_by',
    ];

    public function items()
    {
        return $this->hasMany(AnnualProcurementPlanItem::class, 'annual_procurement_plan_id');
    }
}
