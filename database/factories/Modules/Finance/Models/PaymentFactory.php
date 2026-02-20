<?php

namespace Database\Factories\Modules\Finance\Models;

use App\Modules\Finance\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        return [
            'supplier_id' => \App\Modules\Suppliers\Models\Supplier::factory(),
            'amount' => $this->faker->randomFloat(2, 1000, 100000),
            'status' => 'approved',
            'payment_date' => $this->faker->date(),
            'reference' => $this->faker->unique()->bothify('PAY####'),
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
