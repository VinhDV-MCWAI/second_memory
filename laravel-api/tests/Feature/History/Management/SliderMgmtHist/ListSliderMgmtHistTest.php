<?php

declare(strict_types=1);

namespace Tests\Feature\History\Management\SliderMgmtHist;

use App\Enums\ActionType;
use App\Models\History\Management\SliderMgmtHist;
use App\Models\Management\SliderMgmt;
use App\Models\Master\AdminMst;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

final class ListSliderMgmtHistTest extends TestCase
{
    use GrantsApiAccess;

    private const string URL = 'api/admin/slider-mgmt-hist/list';

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
        $this->getJson(self::URL)->assertStatus(401);
    }

    public function test_lists_history_rows(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['GET', self::URL]]);
        $history = $this->createHistory(SliderMgmt::factory()->create(), $admin);

        $response = $this->call('GET', self::URL, [], $cookies);

        $response->assertStatus(200);
        $this->assertContains($history->id, array_column($response->json('data.data'), 'id'));
    }

    public function test_filters_by_parent_id(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['GET', self::URL]]);
        $target = SliderMgmt::factory()->create();
        $this->createHistory($target, $admin);
        $this->createHistory(SliderMgmt::factory()->create(), $admin);

        $response = $this->call('GET', self::URL, ['slider_mgmt_id' => $target->id], $cookies);

        $response->assertStatus(200);
        $rows = $response->json('data.data');
        $this->assertCount(1, $rows);
        $this->assertSame($target->id, $rows[0]['slider_mgmt_id']);
    }
}
