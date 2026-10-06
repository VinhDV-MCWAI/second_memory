<?php

namespace Database\Factories\Master;

use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiMst>
 */
class ApiMstFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ApiMst::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'type' => fake()->numberBetween(1, 10), // Assuming type is integer
            'name' => fake()->word,
            'path' => fake()->url,
            'is_active' => 1,
            'feature_mst_id' => FeatureMst::factory(),
            'is_delete' => 0,
        ];
    }
}
