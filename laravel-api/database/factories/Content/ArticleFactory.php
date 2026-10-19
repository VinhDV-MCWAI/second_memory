<?php

declare(strict_types=1);

namespace Database\Factories\Content;

use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'content_category_id' => ContentCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->sentence(),
            'body' => '## '.fake()->words(3, true)."\n\n".fake()->paragraph(),
            'sort_order' => 0,
            'is_public' => true,
        ];
    }

    public function private(): static
    {
        return $this->state(['is_public' => false]);
    }
}
