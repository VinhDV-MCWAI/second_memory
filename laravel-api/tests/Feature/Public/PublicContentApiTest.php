<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use Tests\TestCase;

/**
 * Home page categories and articles, no login (ADR-0021, REQ-004).
 */
final class PublicContentApiTest extends TestCase
{
    public function test_categories_in_order_with_public_article_counts_only(): void
    {
        $second = ContentCategory::factory()->create(['name' => 'Thiết kế', 'sort_order' => 20]);
        $first = ContentCategory::factory()->create(['name' => 'Nhận yêu cầu', 'sort_order' => 10]);
        ContentCategory::factory()->private()->create(['sort_order' => 1]);
        Article::factory()->count(2)->create(['content_category_id' => $first->id]);
        Article::factory()->private()->create(['content_category_id' => $first->id]);

        $data = $this->getJson('/api/public/categories')->assertOk()->json('data.data');

        $this->assertSame([$first->slug, $second->slug], array_column($data, 'slug'));
        $this->assertSame([2, 0], array_column($data, 'article_count'));
        $this->assertArrayNotHasKey('id', $data[0]);
    }

    public function test_a_category_lists_its_public_articles_in_order(): void
    {
        $category = ContentCategory::factory()->create();
        Article::factory()->create(['content_category_id' => $category->id, 'title' => 'B', 'sort_order' => 20]);
        Article::factory()->create(['content_category_id' => $category->id, 'title' => 'A', 'sort_order' => 10]);
        Article::factory()->private()->create(['content_category_id' => $category->id, 'title' => 'Hidden']);

        $this->getJson("/api/public/categories/{$category->slug}")->assertOk()
            ->assertJsonPath('data.data.name', $category->name)
            ->assertJsonPath('data.data.articles.0.title', 'A')
            ->assertJsonPath('data.data.articles.1.title', 'B')
            ->assertJsonCount(2, 'data.data.articles');
    }

    public function test_an_article_renders_safe_html(): void
    {
        $article = Article::factory()->create([
            'body' => "## Mục\n\n| A | B |\n|---|---|\n| 1 | 2 |\n\n<script>alert(1)</script>\n\n[x](javascript:alert(1))",
        ])->refresh();

        $html = (string) $this->getJson("/api/public/articles/{$article->category->slug}/{$article->slug}")->assertOk()
            ->assertJsonPath('data.data.category.slug', $article->category->slug)
            ->assertJsonPath('data.data.key', $article->public_key)
            ->json('data.data.body_html');

        $this->assertStringContainsString('<h2>Mục</h2>', $html);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_private_or_missing_things_are_404(): void
    {
        $hiddenCategory = ContentCategory::factory()->private()->create();
        $inHidden = Article::factory()->create(['content_category_id' => $hiddenCategory->id]);
        $hidden = Article::factory()->private()->create();

        $this->getJson("/api/public/categories/{$hiddenCategory->slug}")->assertNotFound();
        $this->getJson("/api/public/articles/{$hiddenCategory->slug}/{$inHidden->slug}")->assertNotFound();
        $this->getJson("/api/public/articles/{$hidden->category->slug}/{$hidden->slug}")->assertNotFound();
        $this->getJson('/api/public/articles/nope/nope')->assertNotFound();
    }
}
