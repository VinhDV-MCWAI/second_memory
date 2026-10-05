<?php

declare(strict_types=1);

namespace Tests\Feature\History\Management\SliderMgmtHist;

use App\Enums\ActionType;
use App\Models\History\Management\SliderMgmtHist;
use App\Models\Management\SliderMgmt;
use App\Models\Master\AdminMst;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

final class DeleteSliderMgmtHistTest extends TestCase
{
    use GrantsApiAccess;

    private const string URL = 'api/admin/slider-mgmt-hist/delete';

    private function createHistory(SliderMgmt $parent, AdminMst $admin, array $overrides = []): SliderMgmtHist
    {
        return SliderMgmtHist::create(array_merge([
            'slider_mgmt_id' => $parent->id,
            'title' => 'Slider history',
            'slug' => 'slider-history',
            'link' => 'https://example.com',
            'image' => 'slider.jpg',
            'status' => 1,
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
        $parent = SliderMgmt::factory()->create();
        $first = $this->createHistory($parent, $admin);
        $second = $this->createHistory($parent, $admin, ['action' => ActionType::UPDATE->value]);

        $response = $this->call('POST', self::URL, ['ids' => [$first->id, $second->id]], $cookies);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('slider_mgmt_hist', ['id' => $first->id]);
        $this->assertDatabaseMissing('slider_mgmt_hist', ['id' => $second->id]);
    }
}
