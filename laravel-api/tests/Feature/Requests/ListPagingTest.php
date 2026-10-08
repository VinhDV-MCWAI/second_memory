<?php

declare(strict_types=1);

namespace Tests\Feature\Requests;

use App\Models\Master\AdminMst;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * Controllers pass $request->validated() to services, so paging and sorting
 * only reach the repository because ListRequest declares rules for them.
 */
final class ListPagingTest extends TestCase
{
    use AuthenticatesAdmins;
    use RefreshDatabase;

    private const LIST_URI = 'api/admin/admin-mst/list';

    public function test_paging_and_sorting_reach_the_repository(): void
    {
        $admin = AdminMst::factory()->create();
        $others = AdminMst::factory()->count(3)->create();
        $cookies = $this->loginAs($admin);

        $response = $this->call('GET', self::LIST_URI, [
            'per_page' => 2,
            'page' => 1,
            'sort_by' => 'id',
            'sort_order' => 'desc',
        ], $cookies);

        $response->assertOk();
        $ids = array_column($response->json('data.data'), 'id');
        $this->assertSame([$others[2]->id, $others[1]->id], $ids);
    }

    public function test_invalid_page_is_rejected(): void
    {
        $admin = AdminMst::factory()->create();
        $cookies = $this->loginAs($admin);

        $this->call('GET', self::LIST_URI, ['page' => 0], $cookies)->assertStatus(422);
    }
}
