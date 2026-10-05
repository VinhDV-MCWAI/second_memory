<?php

declare(strict_types=1);

namespace Tests\Feature\History\Management\EntryMgmtHist;

use App\Enums\ActionType;
use App\Models\History\Management\EntryMgmtHist;
use App\Models\Management\EntryMgmt;
use App\Models\Master\AdminMst;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

final class UpdateEntryMgmtHistTest extends TestCase
{
    use GrantsApiAccess;

    private const string URL = 'api/admin/entry-mgmt-hist/update';

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
        $this->putJson(self::URL.'/1', [])->assertStatus(401);
    }

    public function test_rejects_unknown_history_id(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['PUT', self::URL.'/{id}']]);
        $parent = EntryMgmt::factory()->create();

        $response = $this->call('PUT', self::URL.'/999999', [
            'id' => 999999,
            'entry_mgmt_id' => $parent->id,
            'action' => ActionType::UPDATE->value,
            'author_id' => $admin->id,
        ], $cookies);

        $response->assertStatus(422);
        $this->assertArrayHasKey('id', $response->json('error.messages'));
    }

    public function test_updates_history_row(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['PUT', self::URL.'/{id}']]);
        $parent = EntryMgmt::factory()->create();
        $history = $this->createHistory($parent, $admin);

        $response = $this->call('PUT', self::URL.'/'.$history->id, [
            'id' => $history->id,
            'entry_mgmt_id' => $parent->id,
            'name' => 'Updated entry history',
            'action' => ActionType::UPDATE->value,
            'author_id' => $admin->id,
        ], $cookies);

        $response->assertStatus(200);
        $this->assertDatabaseHas('entry_mgmt_hist', ['id' => $history->id, 'name' => 'Updated entry history']);
    }
}
