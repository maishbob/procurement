<?php

namespace Database\Factories\Modules\Suppliers\Models;

use App\Modules\Suppliers\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'business_name' => $this->faker->companySuffix,
            'supplier_code' => $this->faker->unique()->bothify('SUP-####'),
            'type' => 'company',
            'status' => 'active',
        ];
    }
}
