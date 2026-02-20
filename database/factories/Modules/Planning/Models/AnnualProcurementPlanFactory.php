<?php

namespace Database\Factories\Modules\Planning\Models;

use App\Modules\Planning\Models\AnnualProcurementPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnualProcurementPlanFactory extends Factory
{
    protected $model = AnnualProcurementPlan::class;

    public function definition()
    {
        return [
            'plan_number' => 'APP-' . $this->faker->unique()->numerify('########'),
            'fiscal_year' => $this->faker->year . '/' . ($this->faker->year + 1),
            'description' => $this->faker->sentence,
            'status' => 'draft',
            'created_by' => 1,
        ];
    }
}
