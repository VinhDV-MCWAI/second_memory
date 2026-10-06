<?php

namespace Database\Factories\Master;

use App\Models\Master\FeatureMst;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'name' => $this->faker->name,
            'group_name' => $this->faker->word,
            'status' => 1,
            'is_delete' => 0,
        ];
    }
}
