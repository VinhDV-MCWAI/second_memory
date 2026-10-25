<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use Tests\TestCase;

/**
 * The public search (ADR-0022, ADR-0024): shown articles and categories only, accents ignored, a
 * title outranks a body, typo fallback when nothing matches exactly; where an article matched, the
 * passage of its body with the words marked, and the matching headings with their anchors.
 */
final class PublicSearchApiTest extends TestCase
{
    private const URL = '/api/public/search';

    public function test_shown_articles_and_categories_match_without_accents_title_first(): void
    {
        $group = ContentCategory::factory()->create(['name' => 'Kỹ năng']);
        ContentCategory::factory()->in($group)->create(['name' => 'Hàng đợi Redis', 'slug' => 'hang-doi-redis']);
        ContentCategory::factory()->private()->create(['name' => 'Hàng đợi riêng']);
        $inBody = Article::factory()->create(['title' => 'Cấu hình', 'summary' => null, 'body' => 'Dùng hàng đợi cho việc nền.']);
        $inTitle = Article::factory()->create(['title' => 'Hàng đợi', 'summary' => 'Việc nền.', 'body' => 'Không liên quan.']);
        Article::factory()->private()->create(['title' => 'Hàng đợi nháp']);
        $hiddenBook = Article::factory()->private()->create(['title' => 'Sách nháp']);
        Article::factory()->create(['title' => 'Hàng đợi trong sách nháp', 'parent_id' => $hiddenBook->id]);

        $this->getJson(self::URL.'?q=hang doi')->assertOk()
            ->assertJsonPath('data.match', 'exact')
            ->assertJsonPath('data.articles', [
                ['title' => 'Hàng đợi', 'slug' => $inTitle->slug, 'summary' => 'Việc nền.', 'found_in' => 'title', 'snippet' => null],
                [
                    'title' => 'Cấu hình',
                    'slug' => $inBody->slug,
                    'summary' => null,
                    'found_in' => 'content',
                    'snippet' => 'Dùng <mark>hàng</mark> <mark>đợi</mark> cho việc nền.',
                ],
            ])
            ->assertJsonPath('data.categories', [['name' => 'Hàng đợi Redis', 'slug' => 'hang-doi-redis', 'group' => 'Kỹ năng']]);
        // The last word is a prefix, so results show while typing
        $this->getJson(self::URL.'?q=hang do')->assertOk()->assertJsonCount(2, 'data.articles');
    }

    public function test_snippets_are_cut_around_the_words_and_escaped_and_headings_carry_their_anchor(): void
    {
        $body = str_repeat('Mở đầu dài dòng. ', 20)."Cài **Redis** <b>trước</b>, rồi bật hàng đợi.\n\n"
            ."## Hàng đợi\n\nNội dung.\n\n## Cài đặt\n\n### Hàng đợi\n\n```\n## Hàng đợi trong code\n```\n";
        $article = Article::factory()->create(['title' => 'Ghi chú', 'summary' => null, 'body' => $body]);

        $response = $this->getJson(self::URL.'?q=hang doi')->assertOk();

        $snippet = (string) $response->json('data.articles.0.snippet');
        $this->assertStringStartsWith('…', $snippet);
        $this->assertStringContainsString('Cài Redis &lt;b&gt;trước&lt;/b&gt;, rồi bật <mark>hàng</mark> <mark>đợi</mark>.', $snippet);
        // The two headings with the words, numbered like the article page; not the line inside the code block
        $response->assertJsonPath('data.sections', [
            ['article_title' => 'Ghi chú', 'article_slug' => $article->slug, 'heading' => 'Hàng đợi', 'anchor' => 'hàng-đợi'],
            ['article_title' => 'Ghi chú', 'article_slug' => $article->slug, 'heading' => 'Hàng đợi', 'anchor' => 'hàng-đợi-1'],
        ]);
        $toc = collect($this->getJson("/api/public/articles/{$article->slug}")->json('data.data.toc'))->pluck('id')->all();
        $this->assertSame(['hàng-đợi', 'cài-đặt', 'hàng-đợi-1'], $toc);
    }

    public function test_a_typo_falls_back_to_similar_titles(): void
    {
        $article = Article::factory()->create(['title' => 'Kubernetes cho người mới']);

        $this->getJson(self::URL.'?q=kubernets')->assertOk()
            ->assertJsonPath('data.match', 'fuzzy')
            ->assertJsonPath('data.articles.0.slug', $article->slug);
    }

    public function test_the_query_needs_a_word(): void
    {
        $this->getJson(self::URL.'?q=!!')->assertUnprocessable();
        $this->getJson(self::URL)->assertUnprocessable();
    }
}
