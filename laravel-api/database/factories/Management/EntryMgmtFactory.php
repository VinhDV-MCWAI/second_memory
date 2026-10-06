<?php

namespace Database\Factories\Management;

use App\Models\Management\EntryMgmt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EntryMgmtFactory extends Factory
{
    protected $model = EntryMgmt::class;

    public function definition()
    {
        return [
            'name' => Str::limit($this->faker->word, 45, ''),
            'slug' => Str::limit($this->faker->slug, 45, ''),
            'status' => 1,
            'is_display' => 1,
            'rank_order' => $this->faker->numberBetween(1, 100),
            'is_delete' => 0,
        ];
    }
}
