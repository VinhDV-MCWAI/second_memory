<?php

declare(strict_types=1);

namespace Tests\Feature\History\Management\EntryMgmtHist;

use App\Enums\ActionType;
use App\Models\History\Management\EntryMgmtHist;
use App\Models\Management\EntryMgmt;
use App\Models\Master\AdminMst;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

final class DeleteEntryMgmtHistTest extends TestCase
{
    use GrantsApiAccess;

    private const string URL = 'api/admin/entry-mgmt-hist/delete';

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
        $this->postJson(self::URL, ['ids' => [1]])->assertStatus(401);
    }

    public function test_requires_existing_ids(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['POST', self::URL]]);

        $this->call('POST', self::URL, [], $cookies)->assertStatus(422);
        $this->call('POST', self::URL, ['ids' => [999999]], $cookies)->assertStatus(422);
    }

    public function test_deletes_history_rows(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['POST', self::URL]]);
        $parent = EntryMgmt::factory()->create();
        $first = $this->createHistory($parent, $admin);
        $second = $this->createHistory($parent, $admin, ['action' => ActionType::UPDATE->value]);

        $response = $this->call('POST', self::URL, ['ids' => [$first->id, $second->id]], $cookies);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('entry_mgmt_hist', ['id' => $first->id]);
        $this->assertDatabaseMissing('entry_mgmt_hist', ['id' => $second->id]);
    }
}
