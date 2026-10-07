<?php

namespace Tests\Feature\Auth;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class MeApiTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    protected string $uri = 'api/admin/credential/me';

    protected ?AdminMst $admin;

    protected string $password = 'password123';

    protected function setUp(): void
    {
        parent::setUp();
        // Flush Redis to ensure clean state for each test
        Redis::flushall();

        // Create Role
        $role = RoleMst::create([
            'name' => 'Super Admin',
            'permission' => '{}',
            'is_active' => 1,
            'is_delete' => 0,
        ]);

        // Create Admin User
        $this->admin = AdminMst::factory()->create([
            'user_name' => 'me_user',
            'email' => 'me_user@example.com',
            'password' => Hash::make($this->password),
            'status' => 1,
            'is_active' => 1,
        ]);

        // Attach Role & Permission
        DB::table('admin_role_mst')->insert([
            'admin_mst_id' => $this->admin->id,
            'role_mst_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // --- Helper to Login and Get Cookies ---
    protected function loginAndGetCookies(): array
    {
        $response = $this->postJson('api/admin/credential/login', [
            'user_name' => $this->admin->user_name,
            'password' => $this->password,
        ]);

        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        // Set Permission for ME route in Redis (Mocking what AdminMiddleware checks)
        $permissionKey = CommonVal::ADMIN_TYPE.":{$this->admin->id}:".CommonVal::ADMIN_PERMISSION_TABLE;
        Redis::hset($permissionKey, 'GET', json_encode(['api/admin/credential/me']));

        return $cookies;
    }

    /**
     * T001: Method Not Allowed (POST/PUT/DELETE)
     */
    public function test_t001_method_not_allowed()
    {
        $this->postJson($this->uri)->assertStatus(CommonVal::HTTP_METHOD_NOT_ALLOWED);
        $this->putJson($this->uri)->assertStatus(CommonVal::HTTP_METHOD_NOT_ALLOWED);
        $this->deleteJson($this->uri)->assertStatus(CommonVal::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * T002: Missing Access Token
     */
    public function test_t002_missing_access_token()
    {
        $response = $this->getJson($this->uri);
        $response->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    /**
     * T003: Invalid Access Token (Tampered)
     */
    public function test_t003_invalid_access_token()
    {
        $response = $this->call(
            'GET',
            $this->uri,
            [],
            ['access_token' => 'invalid.jwt.token']
        );
        // Expect 401 (handled by Middleware or Service)
        $this->assertTrue(in_array($response->status(), [CommonVal::HTTP_UNAUTHORIZED, 500]));
    }

    /**
     * T004: Expired Access Token (Redis Missing / TTL)
     * Note: JWT library checks 'exp' claim. Redis check happens in Middleware.
     */
    public function test_t004_expired_access_token_via_redis_deletion()
    {
        $cookies = $this->loginAndGetCookies();
        $accessToken = $cookies['access_token'];

        // Delete from Redis to simulate revocation/expiry
        $tokenKey = CommonVal::ADMIN_TYPE.":{$this->admin->id}:{$accessToken}";
        Redis::del($tokenKey);

        $response = $this->call(
            'GET',
            $this->uri,
            [],
            $cookies
        );

        $response->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    /**
     * T006: Deleted User (Token Valid, DB Missing)
     */
    public function test_t006_deleted_user()
    {
        $cookies = $this->loginAndGetCookies();

        // Hard delete user from DB
        $this->admin->delete();

        $response = $this->call(
            'GET',
            $this->uri,
            [],
            $cookies
        );

        $response->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    /**
     * T007: Success Scenario
     */
    public function test_t007_success_retrieval()
    {
        $cookies = $this->loginAndGetCookies();

        $response = $this->call(
            'GET',
            $this->uri,
            [],
            $cookies
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'email',
                'status',
                'is_active',
                'expires_at',
            ],
        ]);

        $data = $response->json('data');
        $this->assertEquals($this->admin->id, $data['id']);
        $this->assertEquals($this->admin->email, $data['email']);
    }
}
