<?php

namespace Database\Factories\Modules\PurchaseOrders;

use App\Modules\PurchaseOrders\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition()
    {
        return [
            'po_number' => $this->faker->unique()->numerify('PO-####'),
            'status' => 'approved',
        ];
    }
}
