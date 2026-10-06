<?php

namespace Database\Factories\Management;

use App\Models\Management\CategoryMgmt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryMgmtFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CategoryMgmt::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => Str::limit($this->faker->words(3, true), 45, ''),
            'slug' => Str::limit($this->faker->slug, 45, ''),
            'description' => $this->faker->text(140),
            'status' => 1,
            'is_display' => 1,
            'rank_order' => $this->faker->numberBetween(1, 100),
            'is_delete' => 0,
        ];
    }
}
