<?php

declare(strict_types=1);

namespace Tests\Feature\History\Management\EntryDescriptionMgmtHist;

use App\Enums\ActionType;
use App\Models\History\Management\EntryDescriptionMgmtHist;
use App\Models\Management\EntryDescriptionMgmt;
use App\Models\Master\AdminMst;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

final class StoreEntryDescriptionMgmtHistTest extends TestCase
{
    use GrantsApiAccess;

    private const string URL = 'api/admin/entry-description-mgmt-hist/store';

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
        $this->postJson(self::URL, [])->assertStatus(401);
    }

    public function test_requires_parent_action_and_author(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['POST', self::URL]]);

        $response = $this->call('POST', self::URL, [], $cookies);

        $response->assertStatus(422);
        foreach (['entry_description_mgmt_id', 'action', 'author_id'] as $field) {
            $this->assertArrayHasKey($field, $response->json('error.messages'));
        }
    }

    public function test_stores_history_row(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['POST', self::URL]]);
        $parent = EntryDescriptionMgmt::factory()->create();

        $response = $this->call('POST', self::URL, [
            'entry_description_mgmt_id' => $parent->id,
            'title' => 'Description history',
            'summary' => 'Summary',
            'article' => '{"type":"doc","content":[]}',
            'status' => 1,
            'is_display' => 1,
            'rank_order' => 1,
            'action' => ActionType::CREATE->value,
            'author_id' => $admin->id,
        ], $cookies);

        $response->assertStatus(200);
        $this->assertDatabaseHas('entry_description_mgmt_hist', [
            'entry_description_mgmt_id' => $parent->id,
            'title' => 'Description history',
            'author_id' => $admin->id,
        ]);
    }
}
