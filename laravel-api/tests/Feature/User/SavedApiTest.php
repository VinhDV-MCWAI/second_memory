<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Constants\CommonVal;
use App\Models\Content\Article;
use App\Models\User\EndUser;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\AuthenticatesEndUsers;
use Tests\TestCase;

/**
 * Favourites and read later (ADR-0020): only articles the public site shows.
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

    public function test_guests_get_401(): void
    {
        $this->call('GET', self::BASE.'/list')->assertUnauthorized();
        $this->call('POST', self::BASE.'/store', ['kind' => 'favorite', 'article' => $this->key])->assertUnauthorized();
    }
}
