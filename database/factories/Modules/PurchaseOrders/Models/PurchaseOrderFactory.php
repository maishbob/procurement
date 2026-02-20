<?php

namespace Database\Factories\Modules\PurchaseOrders\Models;

use App\Modules\PurchaseOrders\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition()
    {
        return [
            'po_number' => $this->faker->unique()->numerify('PO-####'),
            'title' => $this->faker->sentence(3),
            'po_date' => now(),
            'delivery_date' => now()->addDay(),
            'delivery_location' => $this->faker->address,
            'subtotal' => 1000,
            'total_amount' => 1000,
            'buyer_id' => \App\Models\User::factory(),
            'requester_id' => \App\Models\User::factory(),
            'created_by' => \App\Models\User::factory(),
            'status' => 'approved',
            'supplier_id' => \App\Modules\Suppliers\Models\Supplier::factory(),
            'department_id' => \App\Models\Department::factory(),
        ];
    }
}
