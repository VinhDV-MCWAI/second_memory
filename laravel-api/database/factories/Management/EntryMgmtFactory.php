<?php

declare(strict_types=1);

namespace Database\Factories\Management;

use App\Models\Management\EntryMgmt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EntryMgmt>
 */
class EntryMgmtFactory extends Factory
{
    protected $model = EntryMgmt::class;

    public function definition()
    {
        return [
            'name' => Str::limit(fake()->word, 45, ''),
            'slug' => Str::limit(fake()->slug, 45, ''),
            'status' => 1,
            'is_display' => 1,
            'rank_order' => fake()->numberBetween(1, 100),
            'is_delete' => 0,
        ];
    }
}
