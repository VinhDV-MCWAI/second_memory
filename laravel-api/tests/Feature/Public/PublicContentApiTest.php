<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Models\Content\Article;
use App\Models\Content\CategoryAttachment;
use App\Models\Content\ContentCategory;
use Tests\TestCase;

/**
 * Public groups, categories and articles (ADR-0022): only what is public, no ids.
 */
final class PublicContentApiTest extends TestCase
{
    public function test_groups_then_their_categories_with_visible_article_counts(): void
    {
        $skills = ContentCategory::factory()->create(['name' => 'Kỹ năng', 'sort_order' => 2]);
        $process = ContentCategory::factory()->create(['name' => 'Quy trình', 'sort_order' => 1]);
        $design = ContentCategory::factory()->in($process)->create(['name' => 'Thiết kế']);
        ContentCategory::factory()->in($skills)->private()->create();
        $hiddenGroup = ContentCategory::factory()->private()->create(['sort_order' => 3]);
        ContentCategory::factory()->in($hiddenGroup)->create();
        Article::factory()->inCategory($design)->create();
        Article::factory()->inCategory($design)->private()->create();

        $this->getJson('/api/public/categories')->assertOk()
            ->assertJsonPath('data.data.*.name', ['Quy trình', 'Thiết kế', 'Kỹ năng'])
            ->assertJsonPath('data.data.1.group', 'Quy trình')
            ->assertJsonPath('data.data.1.article_count', 1)
            ->assertJsonMissingPath('data.data.0.id');
    }

    public function test_a_category_shows_its_description_evidence_and_public_articles(): void
    {
        $group = ContentCategory::factory()->create(['name' => 'Kỹ năng']);
        $laravel = ContentCategory::factory()->in($group)->create(['description' => '**Laravel** <script>x</script>']);
        CategoryAttachment::query()->create(['content_category_id' => $laravel->id, 'title' => 'Docs', 'url' => 'https://laravel.com/docs', 'position' => 0]);
        Article::factory()->inCategory($laravel)->create(['title' => 'Cũ', 'updated_at' => now()->subDay()]);
        Article::factory()->inCategory($laravel)->create(['title' => 'Mới']);
        Article::factory()->inCategory($laravel)->private()->create();

        $response = $this->getJson("/api/public/categories/{$laravel->slug}")->assertOk()
            ->assertJsonPath('data.data.group.name', 'Kỹ năng')
            ->assertJsonPath('data.data.attachments.0', ['title' => 'Docs', 'url' => 'https://laravel.com/docs', 'note' => null, 'file' => null])
            ->assertJsonPath('data.data.articles.*.title', ['Mới', 'Cũ']);
        $this->assertStringContainsString('<strong>Laravel</strong>', (string) $response->json('data.data.description_html'));
        $this->assertStringNotContainsString('<script>', (string) $response->json('data.data.description_html'));
    }

    public function test_an_article_renders_safe_html_with_its_public_categories(): void
    {
        $shown = ContentCategory::factory()->create(['name' => 'Thiết kế']);
        $hidden = ContentCategory::factory()->private()->create();
        $article = Article::factory()->create([
            'body' => "## Mục\n\n| A | B |\n|---|---|\n| 1 | 2 |\n\n<script>alert(1)</script>\n\n[x](javascript:alert(1))",
        ])->refresh();
        $article->categories()->attach([$shown->id, $hidden->id]);

        $html = (string) $this->getJson("/api/public/articles/{$article->slug}")->assertOk()
            ->assertJsonPath('data.data.categories', [['name' => 'Thiết kế', 'slug' => $shown->slug]])
            ->assertJsonPath('data.data.key', $article->public_key)
            ->json('data.data.body_html');

        $this->assertStringContainsString('<h2>Mục</h2>', $html);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_private_things_and_pages_under_a_private_article_are_404(): void
    {
        $hiddenGroup = ContentCategory::factory()->private()->create();
        $inHiddenGroup = ContentCategory::factory()->in($hiddenGroup)->create();
        $book = Article::factory()->private()->create();
        $chapter = Article::factory()->create(['parent_id' => $book->id]);
        $section = Article::factory()->create(['parent_id' => $chapter->id]);
        $alone = Article::factory()->create();

        $this->getJson("/api/public/categories/{$hiddenGroup->slug}")->assertNotFound();
        $this->getJson("/api/public/categories/{$inHiddenGroup->slug}")->assertNotFound();
        foreach ([$book, $chapter, $section] as $hiddenArticle) {
            $this->getJson("/api/public/articles/{$hiddenArticle->slug}")->assertNotFound();
        }
        $this->getJson('/api/public/articles/nope')->assertNotFound();
        // An article needs no category to be shown
        $this->getJson("/api/public/articles/{$alone->slug}")->assertOk();
    }
}
