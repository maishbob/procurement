<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierBidItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_bid_id',
        'procurement_item_id',
        'line_number',
        'quantity',
        'unit_price',
        'total_price',
        'is_vatable',
        'vat_amount',
        'total_including_vat',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_including_vat' => 'decimal:2',
        'is_vatable' => 'boolean',
    ];

    public function bid()
    {
        return $this->belongsTo(SupplierBid::class, 'supplier_bid_id');
    }

    public function procurementItem()
    {
        return $this->belongsTo(ProcurementProcessItem::class, 'procurement_item_id');
    }
}
