<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Constants\CommonVal;
use App\Enums\SavedKind;
use App\Models\Content\Article;
use App\Models\Master\AdminMst;
use App\Models\User\EndUser;
use App\Models\User\EndUserSaved;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * Dashboard numbers.
 */
final class DashboardApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const URL = '/api/admin/dashboard/summary';

    public function test_counts_categories_articles_views_and_ranks_the_read_and_saved_articles(): void
    {
        $read = Article::factory()->inCategory()->create(['title' => 'Đọc nhiều', 'view_count' => 7]);
        $saved = Article::factory()->inCategory()->create(['title' => 'Lưu nhiều', 'view_count' => 2]);
        Article::factory()->private()->inCategory()->create();
        foreach (EndUser::factory()->count(2)->create() as $user) {
            EndUserSaved::query()->create(['end_user_id' => $user->id, 'kind' => SavedKind::FAVORITE->value, 'article_id' => $saved->id]);
            // One user in both lists counts once
            EndUserSaved::query()->create(['end_user_id' => $user->id, 'kind' => SavedKind::READ_LATER->value, 'article_id' => $saved->id]);
        }

        $this->call('GET', self::URL, [], $this->loginAs(AdminMst::factory()->create()))
            ->assertOk()
            ->assertJsonPath('data.categories', 3)
            ->assertJsonPath('data.articles', 3)
            ->assertJsonPath('data.public_articles', 2)
            ->assertJsonPath('data.views', 9)
            ->assertJsonPath('data.most_read.*.title', ['Đọc nhiều', 'Lưu nhiều'])
            ->assertJsonPath('data.most_read.0.id', $read->id)
            ->assertJsonPath('data.most_read.0.count', 7)
            ->assertJsonPath('data.most_saved', [['id' => $saved->id, 'title' => 'Lưu nhiều', 'slug' => $saved->slug, 'is_public' => true, 'count' => 2]]);
    }

    public function test_requires_a_session(): void
    {
        $this->getJson(self::URL)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }
}
