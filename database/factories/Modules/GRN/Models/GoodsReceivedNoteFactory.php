<?php

namespace Database\Factories\Modules\GRN\Models;

use App\Modules\GRN\Models\GoodsReceivedNote;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoodsReceivedNoteFactory extends Factory
{
    protected $model = GoodsReceivedNote::class;

    public function definition()
    {
        return [
            'grn_number' => $this->faker->unique()->numerify('GRN-####'),
            // Only allowed values: pending, accepted, partially_accepted, rejected
            'acceptance_status' => $this->faker->randomElement(['pending', 'accepted', 'partially_accepted', 'rejected']),
            'purchase_order_id' => \App\Modules\PurchaseOrders\Models\PurchaseOrder::factory(),
            'supplier_id' => \App\Modules\Suppliers\Models\Supplier::factory(),
            'received_by' => \App\Models\User::factory(),
            'receipt_date' => now(),
            'received_at_location' => $this->faker->address,
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
