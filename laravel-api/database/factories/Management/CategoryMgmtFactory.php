<?php

declare(strict_types=1);

namespace Database\Factories\Management;

use App\Models\Management\CategoryMgmt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CategoryMgmt>
 */
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
            'name' => Str::limit(fake()->words(3, true), 45, ''),
            'slug' => Str::limit(fake()->slug, 45, ''),
            'description' => fake()->text(140),
            'status' => 1,
            'is_display' => 1,
            'rank_order' => fake()->numberBetween(1, 100),
            'is_delete' => 0,
        ];
    }
}
