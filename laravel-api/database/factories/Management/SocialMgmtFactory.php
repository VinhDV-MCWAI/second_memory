<?php

namespace Database\Factories\Management;

use App\Models\Management\SocialMgmt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialMgmt>
 */
class SocialMgmtFactory extends Factory
{
    protected $model = SocialMgmt::class;

    public function definition()
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => substr(str_replace(' ', '-', strtolower($name)), 0, 30),
            'link' => fake()->url(),
            'image' => 'social-'.fake()->numberBetween(1, 100).'.png',
            'status' => 1,
            'is_display' => true,
            'rank_order' => fake()->numberBetween(1, 100),
            'is_delete' => false,
        ];
    }
}
