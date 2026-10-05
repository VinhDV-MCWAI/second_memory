<?php

declare(strict_types=1);

namespace Tests\Feature\History\Management\EntryDescriptionMgmtHist;

use App\Enums\ActionType;
use App\Models\History\Management\EntryDescriptionMgmtHist;
use App\Models\Management\EntryDescriptionMgmt;
use App\Models\Master\AdminMst;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

final class ListEntryDescriptionMgmtHistTest extends TestCase
{
    use GrantsApiAccess;

    private const string URL = 'api/admin/entry-description-mgmt-hist/list';

    private function createHistory(EntryDescriptionMgmt $parent, AdminMst $admin, array $overrides = []): EntryDescriptionMgmtHist
    {
        return EntryDescriptionMgmtHist::create(array_merge([
            'entry_description_mgmt_id' => $parent->id,
            'title' => 'Description history',
            'summary' => 'Summary',
            'article' => '{"type":"doc","content":[]}',
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
        $history = $this->createHistory(EntryDescriptionMgmt::factory()->create(), $admin);

        $response = $this->call('GET', self::URL, [], $cookies);

        $response->assertStatus(200);
        $this->assertContains($history->id, array_column($response->json('data.data'), 'id'));
    }

    public function test_filters_by_parent_id(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['GET', self::URL]]);
        $target = EntryDescriptionMgmt::factory()->create();
        $this->createHistory($target, $admin);
        $this->createHistory(EntryDescriptionMgmt::factory()->create(), $admin);

        $response = $this->call('GET', self::URL, ['entry_description_mgmt_id' => $target->id], $cookies);

        $response->assertStatus(200);
        $rows = $response->json('data.data');
        $this->assertCount(1, $rows);
        $this->assertSame($target->id, $rows[0]['entry_description_mgmt_id']);
    }
}
