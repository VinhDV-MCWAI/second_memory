<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Constants\ContentConst;
use App\Enums\SavedKind;
use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Models\User\EndUser;
use App\Models\User\EndUserSaved;
use Tests\TestCase;

/**
 * What the summary home page and the reading page need (ADR-0024): the category tree with counts,
 * every article in the order of a tab, view and favourite counts. Only what is shown, no ids.
 */
final class PublicReadingApiTest extends TestCase
{
    public function test_the_category_tree_lists_shown_categories_in_order_with_subtree_article_counts(): void
    {
        $skills = ContentCategory::factory()->create(['name' => 'Kỹ năng', 'slug' => 'ky-nang', 'sort_order' => 2, 'view_count' => 7]);
        $process = ContentCategory::factory()->create(['name' => 'Quy trình', 'slug' => 'quy-trinh', 'sort_order' => 1]);
        $backend = ContentCategory::factory()->in($skills)->create(['name' => 'Backend', 'slug' => 'backend']);
        $laravel = ContentCategory::factory()->in($backend)->create(['name' => 'Laravel', 'slug' => 'laravel']);
        $hidden = ContentCategory::factory()->in($skills)->private()->create();
        ContentCategory::factory()->in($hidden)->create();
        // One article in two categories of the same subtree counts once there
        Article::factory()->inCategory($backend)->create()->categories()->attach($laravel->id);
        Article::factory()->inCategory($laravel)->create();
        Article::factory()->inCategory($laravel)->private()->create();
        $this->favorite($skills);
        $this->favorite($skills);

        $this->getJson('/api/public/categories')->assertOk()->assertExactJson([
            'data' => [
                ['name' => 'Quy trình', 'slug' => 'quy-trinh', 'parent_slug' => null, 'depth' => 1, 'article_count' => 0, 'view_count' => 0, 'favorite_count' => 0],
                ['name' => 'Kỹ năng', 'slug' => 'ky-nang', 'parent_slug' => null, 'depth' => 1, 'article_count' => 2, 'view_count' => 7, 'favorite_count' => 2],
                ['name' => 'Backend', 'slug' => 'backend', 'parent_slug' => 'ky-nang', 'depth' => 2, 'article_count' => 2, 'view_count' => 0, 'favorite_count' => 0],
                ['name' => 'Laravel', 'slug' => 'laravel', 'parent_slug' => 'backend', 'depth' => 3, 'article_count' => 2, 'view_count' => 0, 'favorite_count' => 0],
            ],
            'error' => ['status' => false, 'code' => 200, 'messages' => null],
        ]);
    }

    public function test_every_shown_article_in_the_order_of_a_tab(): void
    {
        $read = Article::factory()->create(['title' => 'Đọc nhiều', 'view_count' => 9]);
        $liked = Article::factory()->create(['title' => 'Được thích', 'view_count' => 1]);
        $newest = Article::factory()->create(['title' => 'Mới nhất', 'created_at' => now()->addMinute()]);
        Article::factory()->private()->create(['view_count' => 99]);
        $this->favorite($liked);
        // Read later is not a favourite
        EndUserSaved::query()->create(['end_user_id' => EndUser::factory()->create()->id, 'kind' => SavedKind::READ_LATER, 'article_id' => $read->id]);

        $this->getJson('/api/public/articles?sort=most_read')->assertOk()
            ->assertJsonPath('data.data.articles.*.title', ['Đọc nhiều', 'Được thích', 'Mới nhất'])
            ->assertJsonPath('data.data.total', 3)
            ->assertJsonMissingPath('data.data.articles.0.id');
        $this->getJson('/api/public/articles?sort=most_favorited&per_page=1')->assertOk()
            ->assertJsonPath('data.data.articles.0.title', 'Được thích')
            ->assertJsonPath('data.data.articles.0.favorite_count', 1)
            ->assertJsonPath('data.data.last_page', 3);
        $this->getJson('/api/public/articles')->assertOk()->assertJsonPath('data.data.articles.0.slug', $newest->slug);
        $this->getJson('/api/public/articles?per_page='.(ContentConst::PUBLIC_PER_PAGE_MAX + 1))->assertUnprocessable();
        $this->getJson('/api/public/articles?sort=random')->assertUnprocessable();
    }

    public function test_category_and_article_pages_carry_their_counts_and_a_category_lists_a_larger_page(): void
    {
        $category = ContentCategory::factory()->create(['view_count' => 4]);
        $article = Article::factory()->inCategory($category)->create(['view_count' => 5]);
        Article::factory()->count(2)->inCategory($category)->create();
        $this->favorite($category);
        $this->favorite($article);

        $this->getJson("/api/public/categories/{$category->slug}?per_page=2")->assertOk()
            ->assertJsonPath('data.data.view_count', 4)
            ->assertJsonPath('data.data.favorite_count', 1)
            ->assertJsonCount(2, 'data.data.articles')
            ->assertJsonPath('data.data.last_page', 2);
        $this->getJson("/api/public/categories/{$category->slug}?per_page=".(ContentConst::PUBLIC_PER_PAGE_MAX + 1))->assertUnprocessable();
        $this->getJson("/api/public/articles/{$article->slug}")->assertOk()
            ->assertJsonPath('data.data.view_count', 5)
            ->assertJsonPath('data.data.favorite_count', 1);
    }

    private function favorite(Article|ContentCategory $target): void
    {
        EndUserSaved::query()->create([
            'end_user_id' => EndUser::factory()->create()->id,
            'kind' => SavedKind::FAVORITE,
            ($target instanceof Article ? 'article_id' : 'content_category_id') => $target->id,
        ]);
    }
}
