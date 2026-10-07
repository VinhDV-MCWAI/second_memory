<?php

declare(strict_types=1);

namespace Tests\Feature\Master\AdminMst;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

class DeleteAdminMstTest extends TestCase
{
    use AuthenticatesAdmins;
    use DatabaseTransactions;

    protected string $loginUrl = '/api/admin/credential/login';

    protected string $deleteUrl = '/api/admin/admin-mst/delete';

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushdb();
    }

    protected function getAuthCookies(AdminMst $admin): array
    {
        return $this->loginAsOwner($admin);
    }

    /**
     * Test delete single admin success via POST batch route
     */
    public function test_delete_single_success()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $target = AdminMst::factory()->create();

        // Use call() to ensure cookies are passed correctly
        $response = $this->call('POST', $this->deleteUrl, ['ids' => [$target->id]], $cookies);

        $response->assertStatus(CommonVal::HTTP_OK);

        $this->assertDatabaseHas('admin_mst', [
            'id' => $target->id,
            'is_delete' => 1,
        ]);
    }

    /**
     * Test delete multiple admins success
     */
    public function test_delete_multiple_success()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $target1 = AdminMst::factory()->create();
        $target2 = AdminMst::factory()->create();

        $response = $this->call('POST', $this->deleteUrl, ['ids' => [$target1->id, $target2->id]], $cookies);

        $response->assertStatus(CommonVal::HTTP_OK);

        $this->assertDatabaseHas('admin_mst', [
            'id' => $target1->id,
            'is_delete' => 1,
        ]);
        $this->assertDatabaseHas('admin_mst', [
            'id' => $target2->id,
            'is_delete' => 1,
        ]);
    }

    /**
     * Helper to assert custom validation errors
     */
    protected function assertCustomValidationErrors($response, $keys)
    {
        $response->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $json = $response->json();
        $this->assertArrayHasKey('error', $json);
        $this->assertArrayHasKey('messages', $json['error']);

        foreach ((array) $keys as $key) {
            $this->assertArrayHasKey($key, $json['error']['messages']);
        }
    }

    /**
     * Test missing ids payload
     */
    public function test_missing_ids_payload()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('POST', $this->deleteUrl, [], $cookies);

        $this->assertCustomValidationErrors($response, ['ids']);
    }

    public function test_the_last_active_owner_cannot_be_deleted()
    {
        $owner = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($owner);

        $response = $this->call('POST', $this->deleteUrl, ['ids' => [$owner->id]], $cookies);

        $this->assertCustomValidationErrors($response, 'ids');
        $this->assertDatabaseHas('admin_mst', ['id' => $owner->id, 'is_delete' => 0]);
    }

    public function test_an_owner_can_be_deleted_while_another_owner_remains()
    {
        $owner = AdminMst::factory()->create();
        $cookies = $this->getAuthCookies($owner);
        $otherOwner = AdminMst::factory()->owner()->create();

        $this->call('POST', $this->deleteUrl, ['ids' => [$otherOwner->id]], $cookies)->assertStatus(CommonVal::HTTP_OK);
    }
}
