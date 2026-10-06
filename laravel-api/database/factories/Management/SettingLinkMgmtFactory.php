<?php

namespace Database\Factories\Management;

use App\Models\Management\SettingLinkMgmt;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingLinkMgmtFactory extends Factory
{
    protected $model = SettingLinkMgmt::class;

    public function definition()
    {
        return [
            'key' => $this->faker->unique()->word,
            'value' => $this->faker->url,
            'is_delete' => false,
        ];
    }
}
