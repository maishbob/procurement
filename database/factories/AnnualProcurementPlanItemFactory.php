<?php

namespace Database\Factories;

use App\Models\AnnualProcurementPlanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnualProcurementPlanItemFactory extends Factory
{
    protected $model = AnnualProcurementPlanItem::class;

    public function definition()
    {
        return [
            'category' => $this->faker->word,
            'description' => $this->faker->sentence,
            'planned_quarter' => 'Q' . $this->faker->numberBetween(1, 4),
            'estimated_value' => $this->faker->numberBetween(1000, 100000),
            'sourcing_method' => $this->faker->randomElement(['Open Tender', 'Direct Procurement', 'Request for Quotation']),
        ];
    }
}
