<?php

declare(strict_types=1);

namespace Database\Factories\Content;

use App\Models\Content\ContentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentCategory>
 */
class ContentCategoryFactory extends Factory
{
    protected $model = ContentCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'sort_order' => 0,
            'is_public' => true,
        ];
    }

    public function private(): static
    {
        return $this->state(['is_public' => false]);
    }
}
