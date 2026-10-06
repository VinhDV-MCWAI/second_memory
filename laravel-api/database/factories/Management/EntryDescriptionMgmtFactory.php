<?php

namespace Database\Factories\Management;

use App\Models\Management\EntryDescriptionMgmt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EntryDescriptionMgmt>
 */
class EntryDescriptionMgmtFactory extends Factory
{
    protected $model = EntryDescriptionMgmt::class;

    public function definition()
    {
        return [
            'title' => fake()->sentence(3),
            'summary' => fake()->sentence(10),
            'article' => fake()->paragraph(3),
            'status' => 1,
            'is_display' => true,
            'rank_order' => fake()->numberBetween(1, 100),
            'is_delete' => false,
        ];
    }
}
