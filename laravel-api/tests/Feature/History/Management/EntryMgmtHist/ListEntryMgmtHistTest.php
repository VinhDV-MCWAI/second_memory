<?php

declare(strict_types=1);

namespace Tests\Feature\History\Management\EntryMgmtHist;

use App\Enums\ActionType;
use App\Models\History\Management\EntryMgmtHist;
use App\Models\Management\EntryMgmt;
use App\Models\Master\AdminMst;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

final class ListEntryMgmtHistTest extends TestCase
{
    use GrantsApiAccess;

    private const string URL = 'api/admin/entry-mgmt-hist/list';

    private function createHistory(EntryMgmt $parent, AdminMst $admin, array $overrides = []): EntryMgmtHist
    {
        return EntryMgmtHist::create(array_merge([
            'entry_mgmt_id' => $parent->id,
            'name' => 'Entry history',
            'slug' => 'entry-history',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'action' => ActionType::CREATE->value,
            'author_id' => $admin->id,
            'created_at' => now(),
        ], $overrides));
    }

    public function test_unauthenticated(): void
    {
        $this->getJson(self::URL)->assertStatus(401);
    }

    public function test_lists_history_rows(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['GET', self::URL]]);
        $history = $this->createHistory(EntryMgmt::factory()->create(), $admin);

        $response = $this->call('GET', self::URL, [], $cookies);

        $response->assertStatus(200);
        $this->assertContains($history->id, array_column($response->json('data.data'), 'id'));
    }

    public function test_filters_by_parent_id(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['GET', self::URL]]);
        $target = EntryMgmt::factory()->create();
        $this->createHistory($target, $admin);
        $this->createHistory(EntryMgmt::factory()->create(), $admin);

        $response = $this->call('GET', self::URL, ['entry_mgmt_id' => $target->id], $cookies);

        $response->assertStatus(200);
        $rows = $response->json('data.data');
        $this->assertCount(1, $rows);
        $this->assertSame($target->id, $rows[0]['entry_mgmt_id']);
    }
}
