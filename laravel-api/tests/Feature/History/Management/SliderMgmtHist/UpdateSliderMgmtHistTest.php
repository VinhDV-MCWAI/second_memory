<?php

declare(strict_types=1);

namespace Tests\Feature\History\Management\SliderMgmtHist;

use App\Enums\ActionType;
use App\Models\History\Management\SliderMgmtHist;
use App\Models\Management\SliderMgmt;
use App\Models\Master\AdminMst;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

final class UpdateSliderMgmtHistTest extends TestCase
{
    use GrantsApiAccess;

    private const string URL = 'api/admin/slider-mgmt-hist/update';

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
        $this->putJson(self::URL.'/1', [])->assertStatus(401);
    }

    public function test_rejects_unknown_history_id(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['PUT', self::URL.'/{id}']]);
        $parent = SliderMgmt::factory()->create();

        $response = $this->call('PUT', self::URL.'/999999', [
            'id' => 999999,
            'slider_mgmt_id' => $parent->id,
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
        $parent = SliderMgmt::factory()->create();
        $history = $this->createHistory($parent, $admin);

        $response = $this->call('PUT', self::URL.'/'.$history->id, [
            'id' => $history->id,
            'slider_mgmt_id' => $parent->id,
            'title' => 'Updated slider history',
            'action' => ActionType::UPDATE->value,
            'author_id' => $admin->id,
        ], $cookies);

        $response->assertStatus(200);
        $this->assertDatabaseHas('slider_mgmt_hist', ['id' => $history->id, 'title' => 'Updated slider history']);
    }
}
