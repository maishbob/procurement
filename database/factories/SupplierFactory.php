<?php

namespace Database\Factories;

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
            'supplier_code' => $this->faker->unique()->bothify('SUP####'),
            'kra_pin' => $this->faker->unique()->bothify('P#########'),
            'type' => $this->faker->randomElement(['individual', 'company', 'partnership', 'ngo', 'government']),
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'status' => 'active',
        ];
    }
}
