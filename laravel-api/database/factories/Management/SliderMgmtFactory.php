<?php

namespace Database\Factories\Management;

use App\Models\Management\SliderMgmt;
use Illuminate\Database\Eloquent\Factories\Factory;

class SliderMgmtFactory extends Factory
{
    protected $model = SliderMgmt::class;

    public function definition()
    {
        $title = $this->faker->words(3, true);

        return [
            'title' => $title,
            'slug' => substr(str_replace(' ', '-', strtolower($title)), 0, 30),
            // slider_mgmt.link is varchar(100); faker->url() can exceed it.
            'link' => 'https://'.$this->faker->domainName().'/'.$this->faker->word(),
            'image' => 'slider-'.$this->faker->numberBetween(1, 100).'.jpg',
            'status' => 1,
            'is_delete' => false,
        ];
    }
}
