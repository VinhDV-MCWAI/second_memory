<?php

namespace Database\Factories\Management;

use App\Models\Management\SettingLinkMgmt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SettingLinkMgmt>
 */
class SettingLinkMgmtFactory extends Factory
{
    protected $model = SettingLinkMgmt::class;

    public function definition()
    {
        return [
            'key' => fake()->unique()->word,
            'value' => fake()->url,
            'is_delete' => false,
        ];
    }
}
