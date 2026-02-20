<?php

namespace Database\Factories\Modules\GRN;

use App\Modules\GRN\Models\GoodsReceivedNote;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoodsReceivedNoteFactory extends Factory
{
    protected $model = GoodsReceivedNote::class;

    public function definition()
    {
        return [
            'grn_number' => $this->faker->unique()->numerify('GRN-####'),
            'acceptance_status' => 'accepted',
            'purchase_order_id' => null,
        ];
    }
}
