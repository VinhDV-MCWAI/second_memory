<?php

declare(strict_types=1);

namespace Tests\Feature\History\Management\EntryDescriptionMgmtHist;

use App\Enums\ActionType;
use App\Models\History\Management\EntryDescriptionMgmtHist;
use App\Models\Management\EntryDescriptionMgmt;
use App\Models\Master\AdminMst;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

final class UpdateEntryDescriptionMgmtHistTest extends TestCase
{
    use GrantsApiAccess;

    private const string URL = 'api/admin/entry-description-mgmt-hist/update';

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
        $this->putJson(self::URL.'/1', [])->assertStatus(401);
    }

    public function test_rejects_unknown_history_id(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['PUT', self::URL.'/{id}']]);
        $parent = EntryDescriptionMgmt::factory()->create();

        $response = $this->call('PUT', self::URL.'/999999', [
            'id' => 999999,
            'entry_description_mgmt_id' => $parent->id,
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
        $parent = EntryDescriptionMgmt::factory()->create();
        $history = $this->createHistory($parent, $admin);

        $response = $this->call('PUT', self::URL.'/'.$history->id, [
            'id' => $history->id,
            'entry_description_mgmt_id' => $parent->id,
            'title' => 'Updated description history',
            'action' => ActionType::UPDATE->value,
            'author_id' => $admin->id,
        ], $cookies);

        $response->assertStatus(200);
        $this->assertDatabaseHas('entry_description_mgmt_hist', ['id' => $history->id, 'title' => 'Updated description history']);
    }
}
