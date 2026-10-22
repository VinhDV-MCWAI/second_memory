<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Constants\ContentConst;
use App\Models\Content\Article;
use App\Models\Content\CategoryAttachment;
use App\Models\Content\ContentCategory;
use Tests\TestCase;

/**
 * Public groups, categories and articles (ADR-0022): only what is public, no ids.
 */
final class PublicContentApiTest extends TestCase
{
    public function test_the_home_page_lists_shown_groups_with_their_categories_counts_and_newest_article(): void
    {
        $skills = ContentCategory::factory()->create(['name' => 'Kỹ năng', 'sort_order' => 2]);
        $process = ContentCategory::factory()->create(['name' => 'Quy trình', 'sort_order' => 1]);
        $design = ContentCategory::factory()->in($process)->create(['name' => 'Thiết kế']);
        ContentCategory::factory()->in($skills)->private()->create();
        $hiddenGroup = ContentCategory::factory()->private()->create(['sort_order' => 3]);
        ContentCategory::factory()->in($hiddenGroup)->create();
        Article::factory()->inCategory($design)->create(['title' => 'Cũ', 'created_at' => now()->subDay()]);
        Article::factory()->inCategory($design)->create(['title' => 'Mới']);
        Article::factory()->inCategory($design)->private()->create(['created_at' => now()->addMinute()]);

        $this->getJson('/api/public/home')->assertOk()
            ->assertJsonPath('data.data.*.name', ['Quy trình', 'Kỹ năng'])
            ->assertJsonPath('data.data.0.categories.0.name', 'Thiết kế')
            ->assertJsonPath('data.data.0.categories.0.article_count', 2)
            ->assertJsonPath('data.data.0.categories.0.latest.title', 'Mới')
            ->assertJsonPath('data.data.1.categories', [])
            ->assertJsonMissingPath('data.data.0.id');
    }

    public function test_a_category_shows_its_description_evidence_and_public_articles(): void
    {
        $group = ContentCategory::factory()->create(['name' => 'Kỹ năng']);
        $laravel = ContentCategory::factory()->in($group)->create(['description' => '**Laravel** <script>x</script>']);
        CategoryAttachment::query()->create(['content_category_id' => $laravel->id, 'title' => 'Docs', 'url' => 'https://laravel.com/docs', 'position' => 0]);
        Article::factory()->inCategory($laravel)->create(['title' => 'Cũ', 'created_at' => now()->subDays(2), 'updated_at' => now()]);
        Article::factory()->inCategory($laravel)->create(['title' => 'Mới', 'updated_at' => now()->subDay()]);
        Article::factory()->inCategory($laravel)->private()->create();

        $response = $this->getJson("/api/public/categories/{$laravel->slug}")->assertOk()
            ->assertJsonPath('data.data.group.name', 'Kỹ năng')
            ->assertJsonPath('data.data.attachments.0', ['title' => 'Docs', 'url' => 'https://laravel.com/docs', 'note' => null, 'file' => null])
            ->assertJsonPath('data.data.articles.*.title', ['Mới', 'Cũ'])
            ->assertJsonPath('data.data.total', 2);
        $this->assertStringContainsString('<strong>Laravel</strong>', (string) $response->json('data.data.description_html'));
        $this->assertStringNotContainsString('<script>', (string) $response->json('data.data.description_html'));

        $this->getJson("/api/public/categories/{$laravel->slug}?sort=updated")->assertOk()
            ->assertJsonPath('data.data.articles.*.title', ['Cũ', 'Mới']);
        $this->getJson("/api/public/categories/{$laravel->slug}?sort=nope")->assertUnprocessable();
    }

    public function test_a_group_lists_its_categories_and_their_articles(): void
    {
        $group = ContentCategory::factory()->create();
        $shown = ContentCategory::factory()->in($group)->create(['name' => 'Laravel']);
        $hidden = ContentCategory::factory()->in($group)->private()->create();
        Article::factory()->inCategory($shown)->create(['title' => 'Hàng đợi']);
        Article::factory()->inCategory($hidden)->create(['title' => 'Riêng']);

        $this->getJson("/api/public/categories/{$group->slug}")->assertOk()
            ->assertJsonPath('data.data.group', null)
            ->assertJsonPath('data.data.categories', [['name' => 'Laravel', 'slug' => $shown->slug, 'article_count' => 1]])
            ->assertJsonPath('data.data.articles.*.title', ['Hàng đợi']);
    }

    public function test_a_category_list_narrows_by_a_second_category_and_title_words_and_pages(): void
    {
        $laravel = ContentCategory::factory()->create();
        $redis = ContentCategory::factory()->create();
        $both = Article::factory()->inCategory($laravel)->create(['title' => 'Hàng đợi với Redis']);
        $both->categories()->attach($redis->id);
        Article::factory()->inCategory($laravel)->create(['title' => 'Định tuyến 100%']);
        Article::factory()->count(ContentConst::PUBLIC_PER_PAGE)->inCategory($laravel)->create(['created_at' => now()->subDay()]);

        $url = "/api/public/categories/{$laravel->slug}";
        $this->getJson("{$url}?also={$redis->slug}")->assertOk()->assertJsonPath('data.data.articles.*.title', ['Hàng đợi với Redis']);
        // An unknown second category gives nothing, not everything
        $this->getJson("{$url}?also=nope")->assertOk()->assertJsonPath('data.data.total', 0);
        // Accents and case do not matter; % is a plain character
        $this->getJson("{$url}?q=HANG DOI")->assertOk()->assertJsonPath('data.data.articles.*.title', ['Hàng đợi với Redis']);
        $this->getJson("{$url}?q=0%25")->assertOk()->assertJsonPath('data.data.articles.*.title', ['Định tuyến 100%']);
        $this->getJson("{$url}?page=2")->assertOk()
            ->assertJsonPath('data.data.page', 2)
            ->assertJsonPath('data.data.last_page', 2)
            ->assertJsonPath('data.data.total', ContentConst::PUBLIC_PER_PAGE + 2)
            ->assertJsonCount(2, 'data.data.articles');
    }

    public function test_a_page_of_a_book_has_the_shown_tree_its_way_up_and_previous_next_in_reading_order(): void
    {
        $book = Article::factory()->create(['title' => 'Laravel thực chiến']);
        $intro = Article::factory()->create(['title' => 'Mở đầu', 'parent_id' => $book->id, 'sort_order' => 1]);
        $queues = Article::factory()->create(['title' => 'Hàng đợi', 'parent_id' => $book->id, 'sort_order' => 2]);
        $jobs = Article::factory()->create(['title' => 'Job', 'parent_id' => $queues->id, 'sort_order' => 1]);
        $draft = Article::factory()->private()->create(['parent_id' => $queues->id, 'sort_order' => 2]);
        Article::factory()->create(['title' => 'Dưới bản nháp', 'parent_id' => $draft->id]);
        $end = Article::factory()->create(['title' => 'Kết', 'parent_id' => $book->id, 'sort_order' => 3]);

        $page = fn (Article $article): array => ['title' => $article->title, 'slug' => $article->slug];
        $this->getJson("/api/public/articles/{$jobs->slug}")->assertOk()
            ->assertJsonPath('data.data.book', [
                ...$page($book),
                'children' => [
                    [...$page($intro), 'children' => []],
                    [...$page($queues), 'children' => [[...$page($jobs), 'children' => []]]],
                    [...$page($end), 'children' => []],
                ],
            ])
            ->assertJsonPath('data.data.ancestors', [$page($book), $page($queues)])
            ->assertJsonPath('data.data.previous', $page($queues))
            ->assertJsonPath('data.data.next', $page($end))
            ->assertJsonPath('data.data.related', []);
        $this->getJson("/api/public/articles/{$book->slug}")->assertOk()
            ->assertJsonPath('data.data.previous', null)
            ->assertJsonPath('data.data.next', $page($intro));
        $this->getJson("/api/public/articles/{$end->slug}")->assertOk()->assertJsonPath('data.data.next', null);
    }

    public function test_an_article_outside_a_book_lists_shown_articles_sharing_a_category(): void
    {
        $laravel = ContentCategory::factory()->create();
        $article = Article::factory()->inCategory($laravel)->create();
        $other = Article::factory()->inCategory($laravel)->create();
        Article::factory()->inCategory($laravel)->private()->create();
        Article::factory()->inCategory()->create();

        $this->getJson("/api/public/articles/{$article->slug}")->assertOk()
            ->assertJsonPath('data.data.book', null)
            ->assertJsonPath('data.data.related', [['title' => $other->title, 'slug' => $other->slug]]);
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

        $this->assertStringContainsString('<h2 id="mục">Mục<a href="#mục" class="heading-permalink"', $html);
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
