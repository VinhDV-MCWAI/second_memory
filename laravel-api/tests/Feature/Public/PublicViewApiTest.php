<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Constants\ContentConst;
use App\Models\Content\Article;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * View counts (ADR-0022): the public site reports each article page it renders with a hash of the
 * visitor; one visitor counts once per 30 minutes, and nothing about who read is kept.
 */
final class PublicViewApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // The 30-minute keys live in Redis; tests keep them in memory
        config(['cache.stores.'.ContentConst::VIEW_CACHE_STORE => ['driver' => 'array', 'serialize' => false]]);
    }

    public function test_a_visitor_counts_once_per_window_and_the_article_keeps_its_updated_at(): void
    {
        $article = Article::factory()->create(['updated_at' => now()->subWeek()]);
        $updatedAt = $article->refresh()->updated_at?->toIso8601String();

        $this->reportView($article, 'a')->assertOk()->assertJsonPath('data.counted', true);
        $this->reportView($article, 'a')->assertOk()->assertJsonPath('data.counted', false);
        $this->reportView($article, 'b')->assertOk()->assertJsonPath('data.counted', true);
        $this->travel(ContentConst::VIEW_WINDOW_MINUTES + 1)->minutes();
        $this->reportView($article, 'a')->assertOk()->assertJsonPath('data.counted', true);

        $article->refresh();
        $this->assertSame(3, $article->view_count);
        $this->assertSame($updatedAt, $article->updated_at?->toIso8601String());
    }

    public function test_hidden_or_missing_articles_are_404_and_the_visitor_must_be_a_hash(): void
    {
        $hidden = Article::factory()->private()->create();
        $underHidden = Article::factory()->create(['parent_id' => $hidden->id]);

        $this->reportView($hidden, 'a')->assertNotFound();
        $this->reportView($underHidden, 'a')->assertNotFound();
        $this->postJson('/api/public/articles/nope/view', ['visitor' => hash('sha256', 'a')])->assertNotFound();
        $this->postJson("/api/public/articles/{$underHidden->slug}/view", ['visitor' => 'someone'])->assertUnprocessable();
        $this->assertSame(0, $underHidden->refresh()->view_count);
    }

    private function reportView(Article $article, string $visitor): TestResponse
    {
        return $this->postJson("/api/public/articles/{$article->slug}/view", ['visitor' => hash('sha256', $visitor)]);
    }
}
