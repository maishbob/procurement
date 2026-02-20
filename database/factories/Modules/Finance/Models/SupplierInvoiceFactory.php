<?php

namespace Database\Factories\Modules\Finance\Models;

use App\Modules\Finance\Models\SupplierInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierInvoiceFactory extends Factory
{
    protected $model = SupplierInvoice::class;

    public function definition()
    {
        return [
            'invoice_number' => $this->faker->unique()->numerify('INV-####'),
            'supplier_id' => \App\Modules\Suppliers\Models\Supplier::factory(),
            'purchase_order_id' => \App\Modules\PurchaseOrders\Models\PurchaseOrder::factory(),
            'grn_id' => \App\Modules\GRN\Models\GoodsReceivedNote::factory(),
            'supplier_invoice_number' => $this->faker->unique()->numerify('SUPINV-####'),
            'invoice_date' => $this->faker->date(),
            'due_date' => $this->faker->date(),
            'description' => $this->faker->sentence(),
            'supplier_kra_pin' => $this->faker->regexify('[A-Z0-9]{11}'),
            'etims_control_number' => null,
            'etims_invoice_reference' => null,
            'etims_qr_code' => null,
            'etims_verified_at' => null,
            'is_etims_compliant' => false,
            'subtotal' => 1000,
            'vat_amount' => 160,
            'gross_amount' => 1160,
            'subject_to_wht' => false,
            'wht_type' => null,
            'wht_rate' => null,
            'wht_amount' => 0,
            'net_payable' => 1160,
            'currency' => 'KES',
            'exchange_rate' => 1.0,
            'amount_in_base_currency' => 1160,
            'matches_po' => true,
            'matches_grn' => true,
            'three_way_match_passed' => true,
            'match_variances' => null,
            'variance_tolerance_percent' => 2.0,
            'status' => 'approved',
            'verified_by' => null,
            'verified_at' => null,
            'verification_notes' => null,
            'approved_by' => null,
            'approved_at' => null,
            'approval_notes' => null,
            'payment_id' => null,
            'paid_at' => null,
            'attachments' => null,
            'notes' => null,
            'rejection_reason' => null,
            'created_by' => \App\Models\User::factory(),
            'updated_by' => null,
        ];
    }
}
