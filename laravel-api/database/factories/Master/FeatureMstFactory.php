<?php

declare(strict_types=1);

namespace Database\Factories\Master;

use App\Models\Master\FeatureMst;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeatureMst>
 */
class FeatureMstFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FeatureMst::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => fake()->name,
            'group_name' => fake()->word,
            'status' => 1,
            'is_delete' => 0,
        ];
    }
}
