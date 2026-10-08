<?php

declare(strict_types=1);

namespace Tests\Feature\Requests;

use App\Models\Master\AdminMst;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_only_allowed_columns_sort_and_no_schema_lookup_runs(): void
    {
        $admin = AdminMst::factory()->create(['email' => 'm@example.com']);
        AdminMst::factory()->create(['email' => 'a@example.com']);
        AdminMst::factory()->create(['email' => 'z@example.com']);
        $cookies = $this->loginAs($admin);
        $sql = [];
        DB::listen(function (QueryExecuted $query) use (&$sql): void {
            $sql[] = $query->sql;
        });

        $byEmail = $this->call('GET', self::LIST_URI, ['sort_by' => 'email'], $cookies)->assertOk();
        $this->assertSame(['a@example.com', 'm@example.com', 'z@example.com'], array_column($byEmail->json('data.data'), 'email'));

        // A column outside the allow-list (here a secret) falls back to the default order, id ascending
        $byPassword = $this->call('GET', self::LIST_URI, ['sort_by' => 'password', 'sort_order' => 'desc'], $cookies)->assertOk();
        $ids = array_column($byPassword->json('data.data'), 'id');
        $this->assertSame(array_reverse(AdminMst::query()->orderBy('id')->pluck('id')->all()), $ids);
        $this->assertSame([], array_filter($sql, fn (string $query): bool => str_contains($query, 'information_schema') || str_contains($query, 'pg_attribute')));
    }
}
