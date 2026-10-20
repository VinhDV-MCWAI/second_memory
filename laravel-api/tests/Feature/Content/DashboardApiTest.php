<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Constants\CommonVal;
use App\Models\Content\Article;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * Dashboard numbers.
 */
final class DashboardApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const URL = '/api/admin/dashboard/summary';

    public function test_counts_categories_and_articles(): void
    {
        Article::factory()->count(2)->inCategory()->create();
        Article::factory()->private()->inCategory()->create();

        $this->call('GET', self::URL, [], $this->loginAs(AdminMst::factory()->create()))
            ->assertOk()
            ->assertJsonPath('data', ['categories' => 3, 'articles' => 3, 'public_articles' => 2]);
    }

    public function test_requires_a_session(): void
    {
        $this->getJson(self::URL)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }
}
