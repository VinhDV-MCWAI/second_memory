<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use Tests\TestCase;

/**
 * The public search (ADR-0022): shown articles and categories only, accents ignored, a title
 * outranks a body, typo fallback when nothing matches exactly.
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
        $inTitle = Article::factory()->create(['title' => 'Hàng đợi', 'summary' => 'Việc nền.']);
        Article::factory()->private()->create(['title' => 'Hàng đợi nháp']);
        $hiddenBook = Article::factory()->private()->create(['title' => 'Sách nháp']);
        Article::factory()->create(['title' => 'Hàng đợi trong sách nháp', 'parent_id' => $hiddenBook->id]);

        $this->getJson(self::URL.'?q=hang doi')->assertOk()
            ->assertJsonPath('data.match', 'exact')
            ->assertJsonPath('data.articles', [
                ['title' => 'Hàng đợi', 'slug' => $inTitle->slug, 'summary' => 'Việc nền.'],
                ['title' => 'Cấu hình', 'slug' => $inBody->slug, 'summary' => null],
            ])
            ->assertJsonPath('data.categories', [['name' => 'Hàng đợi Redis', 'slug' => 'hang-doi-redis', 'group' => 'Kỹ năng']]);
        // The last word is a prefix, so results show while typing
        $this->getJson(self::URL.'?q=hang do')->assertOk()->assertJsonCount(2, 'data.articles');
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
