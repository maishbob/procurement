<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_invoice_id',
        'purchase_order_item_id',
        'grn_item_id',
        'line_number',
        'description',
        'quantity',
        'unit_of_measure',
        'unit_price',
        'line_subtotal',
        'vat_rate',
        'vat_amount',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\PurchaseOrders\Models\PurchaseOrderItem::class);
    }

    public function grnItem(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\GRN\Models\GRNItem::class);
    }

    /**
     * Formatted Attributes
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2);
    }

    public function getFormattedLineTotalAttribute(): string
    {
        return number_format($this->line_total, 2);
    }
}
