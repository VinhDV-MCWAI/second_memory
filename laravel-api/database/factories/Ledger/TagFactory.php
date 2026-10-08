<?php

declare(strict_types=1);

namespace Database\Factories\Ledger;

use App\Models\Ledger\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['name' => fake()->unique()->word().'-'.Str::lower(Str::random(4))];
    }
}
