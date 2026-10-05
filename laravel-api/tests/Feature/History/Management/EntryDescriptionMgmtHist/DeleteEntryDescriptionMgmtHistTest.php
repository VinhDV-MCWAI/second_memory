<?php

declare(strict_types=1);

namespace Tests\Feature\History\Management\EntryDescriptionMgmtHist;

use App\Enums\ActionType;
use App\Models\History\Management\EntryDescriptionMgmtHist;
use App\Models\Management\EntryDescriptionMgmt;
use App\Models\Master\AdminMst;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

final class DeleteEntryDescriptionMgmtHistTest extends TestCase
{
    use GrantsApiAccess;

    private const string URL = 'api/admin/entry-description-mgmt-hist/delete';

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
        $parent = EntryDescriptionMgmt::factory()->create();
        $first = $this->createHistory($parent, $admin);
        $second = $this->createHistory($parent, $admin, ['action' => ActionType::UPDATE->value]);

        $response = $this->call('POST', self::URL, ['ids' => [$first->id, $second->id]], $cookies);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('entry_description_mgmt_hist', ['id' => $first->id]);
        $this->assertDatabaseMissing('entry_description_mgmt_hist', ['id' => $second->id]);
    }
}
