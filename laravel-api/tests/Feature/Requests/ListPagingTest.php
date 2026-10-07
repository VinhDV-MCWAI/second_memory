<?php

declare(strict_types=1);

namespace Tests\Feature\Requests;

use App\Models\Management\BannerMgmt;
use App\Models\Master\AdminMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsApiAccess;
use Tests\TestCase;

/**
 * Controllers pass $request->validated() to services, so paging and sorting
 * only reach the repository because ListRequest declares rules for them.
 */
final class ListPagingTest extends TestCase
{
    use GrantsApiAccess;
    use RefreshDatabase;

    private const LIST_URI = 'api/admin/banner-mgmt/list';

    public function test_paging_and_sorting_reach_the_repository(): void
    {
        $admin = AdminMst::factory()->create();
        $banners = BannerMgmt::factory()->count(3)->create();
        $cookies = $this->loginWithAccess($admin, [['GET', self::LIST_URI]]);

        $response = $this->call('GET', self::LIST_URI, [
            'per_page' => 2,
            'page' => 1,
            'sort_by' => 'id',
            'sort_order' => 'desc',
        ], $cookies);

        $response->assertOk();
        $ids = array_column($response->json('data.data'), 'id');
        $this->assertSame([$banners[2]->id, $banners[1]->id], $ids);
    }

    public function test_invalid_page_is_rejected(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginWithAccess($admin, [['GET', self::LIST_URI]]);

        $this->call('GET', self::LIST_URI, ['page' => 0], $cookies)->assertStatus(422);
    }
}
