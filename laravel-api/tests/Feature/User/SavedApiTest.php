<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Constants\CommonVal;
use App\Constants\ContentConst;
use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Models\User\EndUser;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\AuthenticatesEndUsers;
use Tests\TestCase;

/**
 * Favourites and read later (ADR-0020, ADR-0024): only articles and categories the public site
 * shows; a category only as a favourite; a read-later article keeps the heading the reader reached.
 */
final class SavedApiTest extends TestCase
{
    use AuthenticatesAdmins, AuthenticatesEndUsers;

    private const BASE = '/api/user/saved';

    private Article $article;

    private string $key;

    /** @var array<string, string> */
    private array $cookies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->article = Article::factory()->create(['title' => 'Bộ câu hỏi làm rõ']);
        $this->key = (string) $this->call('GET', "/api/public/articles/{$this->article->slug}")->assertOk()->json('data.data.key');
        $this->cookies = $this->loginAsEndUser(EndUser::factory()->create());
    }

    public function test_save_list_and_remove_articles(): void
    {
        $other = Article::factory()->create(['title' => 'Checklist nghiệm thu']);
        $otherKey = (string) $other->refresh()->public_key;

        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'article' => $this->key], $this->cookies)->assertOk();
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'article' => $this->key], $this->cookies)->assertOk();
        $this->call('POST', self::BASE.'/store', ['kind' => 'read_later', 'article' => $otherKey], $this->cookies)->assertOk();

        $this->call('GET', self::BASE.'/list', [], $this->cookies)->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.kind', 'read_later')
            ->assertJsonPath('data.data.0.key', $otherKey)
            ->assertJsonPath('data.data.0.title', 'Checklist nghiệm thu')
            ->assertJsonPath('data.data.1.kind', 'favorite')
            ->assertJsonPath('data.data.1.article_slug', $this->article->slug);
        $this->call('GET', self::BASE.'/list', ['kind' => 'favorite'], $this->cookies)->assertJsonCount(1, 'data.data');

        $this->call('POST', self::BASE.'/delete', ['kind' => 'favorite', 'article' => $this->key], $this->cookies)->assertOk();
        $this->call('GET', self::BASE.'/list', ['kind' => 'favorite'], $this->cookies)->assertJsonCount(0, 'data.data');
    }

    public function test_hidden_articles_cannot_be_saved_and_drop_out(): void
    {
        $private = Article::factory()->private()->create();
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'article' => (string) $private->refresh()->public_key], $this->cookies)->assertNotFound();

        $this->call('POST', self::BASE.'/store', ['kind' => 'read_later', 'article' => $this->key], $this->cookies)->assertOk();
        // Hidden by a private parent article, without leaking the title
        $this->article->update(['parent_id' => Article::factory()->private()->create()->id]);
        $this->call('GET', self::BASE.'/list', [], $this->cookies)->assertJsonCount(0, 'data.data');
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'article' => $this->key], $this->cookies)->assertNotFound();
    }

    public function test_deleting_the_article_removes_what_pointed_at_it(): void
    {
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'article' => $this->key], $this->cookies)->assertOk();
        $this->article->delete();

        $this->assertDatabaseCount('end_user_saved', 0);
    }

    public function test_an_article_key_and_a_known_list(): void
    {
        $this->call('POST', self::BASE.'/store', ['kind' => 'wish', 'article' => 'not-a-key'], $this->cookies)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['kind', 'article']]]);
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite'], $this->cookies)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    }

    public function test_a_category_is_saved_as_a_favourite_only(): void
    {
        $category = ContentCategory::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'category' => 'laravel'], $this->cookies)->assertOk();
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'category' => 'laravel'], $this->cookies)->assertOk();
        $this->call('GET', self::BASE.'/list', [], $this->cookies)->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.type', 'category')
            ->assertJsonPath('data.data.0.key', 'laravel')
            ->assertJsonPath('data.data.0.title', 'Laravel')
            ->assertJsonPath('data.data.0.category_slug', 'laravel')
            ->assertJsonPath('data.data.0.article_slug', null);

        $this->call('POST', self::BASE.'/store', ['kind' => 'read_later', 'category' => 'laravel'], $this->cookies)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['category']]]);
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'category' => 'laravel', 'article' => $this->key], $this->cookies)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'category' => 'nope'], $this->cookies)->assertNotFound();

        // Hidden: out of the list but kept; shown again: back
        $category->update(['is_public' => false]);
        $this->call('GET', self::BASE.'/list', [], $this->cookies)->assertJsonCount(0, 'data.data');
        $category->update(['is_public' => true]);
        $this->call('GET', self::BASE.'/list', [], $this->cookies)->assertJsonCount(1, 'data.data');

        $this->call('POST', self::BASE.'/delete', ['kind' => 'favorite', 'category' => 'laravel'], $this->cookies)->assertOk();
        $this->assertDatabaseCount('end_user_saved', 0);
    }

    public function test_a_read_later_article_keeps_the_heading_reached_until_hidden_or_deleted(): void
    {
        $this->call('POST', self::BASE.'/store', ['kind' => 'read_later', 'article' => $this->key], $this->cookies)->assertOk();
        $this->call('GET', self::BASE.'/list', [], $this->cookies)->assertJsonPath('data.data.0.anchor', null);

        $this->call('POST', self::BASE.'/position', ['article' => $this->key, 'anchor' => 'hàng-đợi'], $this->cookies)->assertOk();
        $this->call('GET', self::BASE.'/list', [], $this->cookies)
            ->assertJsonPath('data.data.0.type', 'article')
            ->assertJsonPath('data.data.0.anchor', 'hàng-đợi')
            ->assertJsonPath('data.data.0.read_at', fn (?string $at): bool => $at !== null);
        $this->call('POST', self::BASE.'/position', ['article' => $this->key, 'anchor' => null], $this->cookies)->assertOk();
        $this->call('GET', self::BASE.'/list', [], $this->cookies)->assertJsonPath('data.data.0.anchor', null);
        $this->call('POST', self::BASE.'/position', ['article' => $this->key, 'anchor' => str_repeat('a', ContentConst::READING_ANCHOR_MAX + 1)], $this->cookies)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);

        // A favourite is not a read-later entry
        $other = Article::factory()->create();
        $otherKey = (string) $other->refresh()->public_key;
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'article' => $otherKey], $this->cookies)->assertOk();
        $this->call('POST', self::BASE.'/position', ['article' => $otherKey, 'anchor' => 'x'], $this->cookies)->assertNotFound();

        // Private: hidden with its position kept; public again: back where the reader was
        $this->call('POST', self::BASE.'/position', ['article' => $this->key, 'anchor' => 'cài-đặt'], $this->cookies)->assertOk();
        $this->article->update(['is_public' => false]);
        $this->call('GET', self::BASE.'/list', ['kind' => 'read_later'], $this->cookies)->assertJsonCount(0, 'data.data');
        $this->call('POST', self::BASE.'/position', ['article' => $this->key, 'anchor' => 'x'], $this->cookies)->assertNotFound();
        $this->article->update(['is_public' => true]);
        $this->call('GET', self::BASE.'/list', ['kind' => 'read_later'], $this->cookies)->assertJsonPath('data.data.0.anchor', 'cài-đặt');
    }

    public function test_guests_get_401(): void
    {
        $this->call('GET', self::BASE.'/list')->assertUnauthorized();
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'article' => $this->key])->assertUnauthorized();
        $this->call('POST', self::BASE.'/position', ['article' => $this->key, 'anchor' => 'x'])->assertUnauthorized();
    }
}
