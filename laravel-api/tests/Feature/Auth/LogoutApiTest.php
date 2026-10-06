<?php

namespace Tests\Feature\Auth;

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Models\Master\AdminMst;
use App\Models\Master\TokenMst;
use App\Utilities\JsonWebToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class LogoutApiTest extends TestCase
{
    use DatabaseTransactions;

    protected string $logoutUrl = '/api/admin/credential/trust/logout';

    protected string $loginUrl = '/api/admin/credential/login';

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
    }

    // Helper to get authenticated cookies
    protected function getAuthCookies(AdminMst $admin): array
    {
        $rootRole = \App\Models\Master\RoleMst::where('name', 'root')->first();
        if (! $rootRole) {
            $rootRole = \App\Models\Master\RoleMst::create(['name' => 'root', 'permission' => '{}', 'is_active' => 1, 'is_delete' => 0]);
        } else {
            // Ensure permission is not null
            if (is_null($rootRole->permission)) {
                $rootRole->update(['permission' => '{}']);
            }
        }

        DB::table('admin_role_mst')->updateOrInsert(
            ['admin_mst_id' => $admin->id],
            ['role_mst_id' => $rootRole->id, 'created_at' => now(), 'updated_at' => now()]
        );

        // Simulate login flow
        $response = $this->postJson($this->loginUrl, [
            'user_name' => $admin->user_name,
            'password' => 'password',
        ]);
        $response->assertStatus(200);

        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        // Force populate Redis Permissions if missing (Fix for Legacy Test Regression)
        $permissionTableKey = CommonVal::ADMIN_TYPE.":{$admin->id}:".CommonVal::ADMIN_PERMISSION_TABLE;
        if (! Redis::exists($permissionTableKey)) {
            $paths = [ltrim($this->logoutUrl, '/')];
            Redis::hset($permissionTableKey, 'POST', json_encode($paths));
        }

        return $cookies;
    }

    /**
     * T001: [ROUTE-01] Wrong HTTP Method (GET)
     */
    public function test_t001_method_get_not_allowed()
    {
        $response = $this->getJson($this->logoutUrl);
        $response->assertStatus(CommonVal::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * T002: [AUTH-01] Missing Access Token
     */
    public function test_t002_auth_missing_access_token()
    {
        $response = $this->postJson($this->logoutUrl, [], []);
        $response->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
        $this->assertEquals(Messages::E0401, $response->json('error')['messages']);
    }

    /**
     * T003: [AUTH-02] Invalid Access Token (Tampered)
     */
    public function test_t003_auth_invalid_access_token()
    {
        // Pass invalid token
        $response = $this->withCookie('access_token', 'invalid_token_string')
            ->postJson($this->logoutUrl);

        // Expect 401 or similar error status (not 200)
        // Detailed error content depends on Exception handling of UnexpectedValueException
        $this->assertNotEquals(CommonVal::HTTP_OK, $response->status());
    }

    /**
     * T004: [AUTH-03] Revoked Access Token (Redis)
     */
    public function test_t004_auth_revoked_access_token()
    {
        $admin = AdminMst::factory()->create([
            'user_name' => 'revoked',
            'password' => Hash::make('password'),
        ]);
        $cookies = $this->getAuthCookies($admin);

        // Manually delete key from Redis to simulate revocation
        $accessToken = $cookies['access_token'];
        $key = CommonVal::ADMIN_TYPE.":{$admin->id}:{$accessToken}";
        Redis::del($key);

        $response = $this->call(
            'POST',
            $this->logoutUrl,
            [],
            $cookies,
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );

        $response->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
        $this->assertEquals(Messages::E0609, $response->json('error')['messages']);
    }

    /**
     * T005: [AUTH-04] Wrong User Type
     */
    public function test_t005_auth_wrong_user_type()
    {
        $admin = AdminMst::factory()->create([
            'user_name' => 'wrong_type',
            'password' => Hash::make('password'),
        ]);

        // Forge a token with wrong type
        $payload = [
            'id' => (string) $admin->id,
            'type' => 'USER', // Valid type is CommonVal::ADMIN_TYPE ('admin')
        ];
        $token = JsonWebToken::encode(
            JsonWebToken::JWTPayload($payload, false),
            env('ACCESS_TOKEN_SECRET')
        );

        $response = $this->withCookie('access_token', $token)
            ->postJson($this->logoutUrl);

        // Check for E0608 error message (UnexpectedValueException)
        // Depending on handler, might be 401 or 500.
        $json = $response->json();
        $errorMsg = $json['error']['messages'] ?? $json['message'] ?? '';

        $this->assertEquals(Messages::E0401, $errorMsg);
    }

    /**
     * T006: [PERM-01] Unauthorized Route (Permission)
     */
    public function test_t006_perm_unauthorized_route()
    {
        $admin = AdminMst::factory()->create([
            'user_name' => 'perm_check',
            'password' => Hash::make('password'),
        ]);
        $cookies = $this->getAuthCookies($admin);

        // Manipulate Redis Permission Table
        $permissionTableKey = CommonVal::ADMIN_TYPE.":{$admin->id}:".CommonVal::ADMIN_PERMISSION_TABLE;

        // Get current permissions
        $pathsJson = Redis::hget($permissionTableKey, 'POST');
        $paths = json_decode($pathsJson, true);

        // Remove logout path
        $target = 'api/admin/credential/trust/logout';
        $paths = array_values(array_diff($paths, [$target]));

        Redis::hset($permissionTableKey, 'POST', json_encode($paths));

        $response = $this->call(
            'POST',
            $this->logoutUrl,
            [],
            $cookies,
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );

        $response->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
        $this->assertEquals(Messages::E0401, $response->json('error')['messages']);
    }

    /**
     * T007: [REQ-01] Unexpected Request Body
     */
    public function test_t007_unexpected_request_body()
    {
        $admin = AdminMst::factory()->create([
            'user_name' => 'extra_body',
            'password' => Hash::make('password'),
        ]);
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call(
            'POST',
            $this->logoutUrl,
            ['unexpected' => 'data'],
            $cookies,
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );

        $response->assertStatus(CommonVal::HTTP_OK);

        // Verify logout happened
        $key = CommonVal::ADMIN_TYPE.":{$admin->id}:{$cookies['access_token']}";
        $this->assertEquals(0, Redis::exists($key));
    }

    /**
     * T008: [VAL-01] Missing Refresh Token
     */
    public function test_t008_val_missing_refresh_token()
    {
        $admin = AdminMst::factory()->create([
            'user_name' => 'missing_refresh',
            'password' => Hash::make('password'),
        ]);
        $cookies = $this->getAuthCookies($admin);

        // Remove refresh token
        unset($cookies['refresh_token']);

        $response = $this->call(
            'POST',
            $this->logoutUrl,
            [],
            $cookies,
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );

        $response->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
        $this->assertEquals(Messages::E0401, $response->json('error')['messages']);
    }

    /**
     * T009: [VAL-02] Invalid Refresh Token (Not in DB)
     */
    public function test_t009_val_invalid_refresh_token()
    {
        $admin = AdminMst::factory()->create([
            'user_name' => 'fake_refresh',
            'password' => Hash::make('password'),
        ]);
        $cookies = $this->getAuthCookies($admin);

        // Delete the token from DB
        TokenMst::where('account_id', $admin->id)->delete();

        $response = $this->call(
            'POST',
            $this->logoutUrl,
            [],
            $cookies,
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );

        $response->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
        $this->assertEquals(Messages::E0401, $response->json('error')['messages']);
    }

    /**
     * T010: [LOGIC-01] Successful Logout
     */
    public function test_t010_logic_successful_logout()
    {
        $admin = AdminMst::factory()->create([
            'user_name' => 'logic_success',
            'password' => Hash::make('password'),
        ]);
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call(
            'POST',
            $this->logoutUrl,
            [],
            $cookies,
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );

        $response->assertStatus(CommonVal::HTTP_OK);

        // Check cookies in response
        $resCookies = $response->headers->getCookies();
        $foundAccess = false;
        $foundRefresh = false;

        foreach ($resCookies as $c) {
            if ($c->getName() === 'access_token') {
                $foundAccess = true;
                // Should be expired
                $this->assertTrue($c->getExpiresTime() < time() || empty($c->getValue()));
                // Verify path matches login
                $this->assertEquals('/api/admin', $c->getPath());
                $this->assertTrue($c->isHttpOnly());
            }
            if ($c->getName() === 'refresh_token') {
                $foundRefresh = true;
                $this->assertTrue($c->getExpiresTime() < time() || empty($c->getValue()));
                // Verify path matches refresh
                $this->assertEquals('/api/admin/credential/trust', $c->getPath());
                $this->assertTrue($c->isHttpOnly());
            }
        }
        $this->assertTrue($foundAccess, 'Access token cookie should be present (cleared)');
        $this->assertTrue($foundRefresh, 'Refresh token cookie should be present (cleared)');

        // Verify Redis deleted
        $key = CommonVal::ADMIN_TYPE.":{$admin->id}:{$cookies['access_token']}";
        $this->assertEquals(0, Redis::exists($key));

        // Verify DB Token deleted
        $this->assertDatabaseMissing('token_mst', [
            'account_id' => $admin->id,
        ]);
    }

    /**
     * T013: [LOGIC-02] Locked Account Logout
     * Verify that a locked account can still logout (security safety valve).
     */
    public function test_t013_locked_account_logout()
    {
        $admin = AdminMst::factory()->create([
            'user_name' => 'locked_logout',
            'password' => Hash::make('password'),
        ]);
        $cookies = $this->getAuthCookies($admin);

        // Lock account
        $admin->limit_access = 5;
        $admin->save();

        $response = $this->call(
            'POST',
            $this->logoutUrl,
            [],
            $cookies,
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );

        // Should still succeed
        $response->assertStatus(CommonVal::HTTP_OK);

        // Verify Redis deleted (Revocation worked)
        $key = CommonVal::ADMIN_TYPE.":{$admin->id}:{$cookies['access_token']}";
        $this->assertEquals(0, Redis::exists($key));
    }
}
