<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementProcessItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_process_id',
        'requisition_item_id',
        'line_number',
        'description',
        'specifications',
        'quantity',
        'unit_of_measure',
        'estimated_unit_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'estimated_unit_price' => 'decimal:2',
    ];

    public function process()
    {
        return $this->belongsTo(ProcurementProcess::class, 'procurement_process_id');
    }

    public function requisitionItem()
    {
        return $this->belongsTo(RequisitionItem::class);
    }
}
