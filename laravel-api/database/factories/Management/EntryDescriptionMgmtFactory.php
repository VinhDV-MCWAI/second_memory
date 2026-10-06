<?php

namespace Database\Factories\Management;

use App\Models\Management\EntryDescriptionMgmt;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntryDescriptionMgmtFactory extends Factory
{
    protected $model = EntryDescriptionMgmt::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(3),
            'summary' => $this->faker->sentence(10),
            'article' => $this->faker->paragraph(3),
            'status' => 1,
            'is_display' => true,
            'rank_order' => $this->faker->numberBetween(1, 100),
            'is_delete' => false,
        ];
    }
}
