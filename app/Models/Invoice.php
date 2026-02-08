<?php

namespace App\Models;

/**
 * Invoice Model Alias
 * 
 * Provides compatibility for controllers importing from App\Models
 * Maps to SupplierInvoice in the Finance module
 */
class Invoice extends \App\Modules\Finance\Models\SupplierInvoice
{
}
