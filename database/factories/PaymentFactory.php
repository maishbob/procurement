<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        return [
            'payment_number' => $this->faker->unique()->bothify('PAYNUM####'),
            'supplier_id' => \App\Models\Supplier::factory(),
            'status' => 'approved',
            'payment_date' => $this->faker->date(),
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'cheque', 'mpesa', 'cash']),
            'gross_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'wht_amount' => 5000,
            'wht_rate' => 5,
            'net_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'reference_number' => $this->faker->unique()->bothify('PAY####'),
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
