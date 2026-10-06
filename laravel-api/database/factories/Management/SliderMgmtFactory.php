<?php

declare(strict_types=1);

namespace Database\Factories\Management;

use App\Models\Management\SliderMgmt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SliderMgmt>
 */
class SliderMgmtFactory extends Factory
{
    protected $model = SliderMgmt::class;

    public function definition()
    {
        $title = fake()->words(3, true);

        return [
            'title' => $title,
            'slug' => substr(str_replace(' ', '-', strtolower($title)), 0, 30),
            // slider_mgmt.link is varchar(100); faker->url() can exceed it.
            'link' => 'https://'.fake()->domainName().'/'.fake()->word(),
            'image' => 'slider-'.fake()->numberBetween(1, 100).'.jpg',
            'status' => 1,
            'is_delete' => false,
        ];
    }
}
