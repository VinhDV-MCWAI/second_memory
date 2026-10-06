<?php

namespace Database\Factories\Management;

use App\Models\Management\BannerMgmt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BannerMgmt>
 */
class BannerMgmtFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BannerMgmt::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => fake()->text(40), // Limit to 50
            'slug' => Str::limit(fake()->slug, 45, ''),
            'description' => fake()->text(200),
            'position' => 'top',
            'status' => 1,
            'is_delete' => 0,
        ];
    }
}
