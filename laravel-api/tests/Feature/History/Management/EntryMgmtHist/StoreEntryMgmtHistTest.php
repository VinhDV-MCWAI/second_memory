<?php

declare(strict_types=1);

namespace Tests\Feature\History\Management\EntryMgmtHist;

use App\Enums\ActionType;
use App\Models\History\Management\EntryMgmtHist;
use App\Models\Management\EntryMgmt;
use App\Models\Master\AdminMst;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

final class StoreEntryMgmtHistTest extends TestCase
{
    use GrantsApiAccess;

    private const string URL = 'api/admin/entry-mgmt-hist/store';

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
        $this->postJson(self::URL, [])->assertStatus(401);
    }

    public function test_requires_parent_action_and_author(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['POST', self::URL]]);

        $response = $this->call('POST', self::URL, [], $cookies);

        $response->assertStatus(422);
        foreach (['entry_mgmt_id', 'action', 'author_id'] as $field) {
            $this->assertArrayHasKey($field, $response->json('error.messages'));
        }
    }

    public function test_stores_history_row(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['POST', self::URL]]);
        $parent = EntryMgmt::factory()->create();

        $response = $this->call('POST', self::URL, [
            'entry_mgmt_id' => $parent->id,
            'name' => 'Entry history',
            'slug' => 'entry-history',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'action' => ActionType::CREATE->value,
            'author_id' => $admin->id,
        ], $cookies);

        $response->assertStatus(200);
        $this->assertDatabaseHas('entry_mgmt_hist', [
            'entry_mgmt_id' => $parent->id,
            'name' => 'Entry history',
            'author_id' => $admin->id,
        ]);
    }
}
