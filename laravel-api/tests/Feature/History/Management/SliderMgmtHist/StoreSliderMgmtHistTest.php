<?php

declare(strict_types=1);

namespace Tests\Feature\History\Management\SliderMgmtHist;

use App\Enums\ActionType;
use App\Models\History\Management\SliderMgmtHist;
use App\Models\Management\SliderMgmt;
use App\Models\Master\AdminMst;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

final class StoreSliderMgmtHistTest extends TestCase
{
    use GrantsApiAccess;

    private const string URL = 'api/admin/slider-mgmt-hist/store';

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
        $this->postJson(self::URL, [])->assertStatus(401);
    }

    public function test_requires_parent_action_and_author(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['POST', self::URL]]);

        $response = $this->call('POST', self::URL, [], $cookies);

        $response->assertStatus(422);
        foreach (['slider_mgmt_id', 'action', 'author_id'] as $field) {
            $this->assertArrayHasKey($field, $response->json('error.messages'));
        }
    }

    public function test_stores_history_row(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['POST', self::URL]]);
        $parent = SliderMgmt::factory()->create();

        $response = $this->call('POST', self::URL, [
            'slider_mgmt_id' => $parent->id,
            'title' => 'Slider history',
            'slug' => 'slider-history',
            'link' => 'https://example.com',
            'image' => 'slider.jpg',
            'status' => 1,
            'action' => ActionType::CREATE->value,
            'author_id' => $admin->id,
        ], $cookies);

        $response->assertStatus(200);
        $this->assertDatabaseHas('slider_mgmt_hist', [
            'slider_mgmt_id' => $parent->id,
            'title' => 'Slider history',
            'author_id' => $admin->id,
        ]);
    }
}
