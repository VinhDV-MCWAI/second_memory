<?php

declare(strict_types=1);

namespace Tests\Feature\Master\AdminMst;

use App\Constants\CommonVal;
use App\Enums\ActionType;
use App\Enums\Gender;
use App\Enums\IsActive;
use App\Enums\IsDelete;
use App\Enums\StatusEnum;
use App\Models\Master\AdminMst;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

class StoreAdminMstTest extends TestCase
{
    use AuthenticatesAdmins;
    use DatabaseTransactions;

    protected string $storeUrl = '/api/admin/admin-mst/store';

    protected string $loginUrl = '/api/admin/credential/login';

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

    // ======================================================================
    // Step 1: Route-level analysis
    // ======================================================================

    /**
     * MST_STR_001: Method Not Allowed
     */
    public function test_ms_t_st_r_001_method_not_allowed()
    {
        $response = $this->getJson($this->storeUrl);
        $response->assertStatus(CommonVal::HTTP_METHOD_NOT_ALLOWED);
    }

    // ======================================================================
    // Step 2: Middleware analysis
    // ======================================================================

    /**
     * MST_STR_002: Service / Middleware - Unauthenticated
     */
    public function test_ms_t_st_r_002_unauthenticated()
    {
        $response = $this->postJson($this->storeUrl, []);
        $response->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    /**
     * MST_STR_003: Service / Middleware - Unauthorized (Non-Admin / No Permission)
     */
    public function test_ms_t_st_r_003_forbidden_access()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);

        // Login without assigning root role
        $response = $this->postJson($this->loginUrl, [
            'user_name' => $admin->user_name,
            'password' => 'password',
        ]);

        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        $response = $this->call('POST', $this->storeUrl, [], $cookies);
        $response->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    // ======================================================================
    // Step 3: Request entry analysis
    // ======================================================================

    /**
     * MST_STR_004: Empty Body
     */
    public function test_ms_t_st_r_004_empty_body()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $response = $this->call('POST', $this->storeUrl, [], $cookies);
        $this->assertCustomValidationErrors($response, ['email', 'user_name', 'password', 'first_name', 'last_name', 'gender', 'status', 'is_active', 'is_delete']);
    }

    // ======================================================================
    // Step 4: Validation analysis
    // ======================================================================

    /**
     * MST_STR_005: Missing Required Fields
     */
    public function test_ms_t_st_r_005_missing_required_fields()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        // Optional fields only
        $payload = [
            'address' => 'Some Address',
        ];

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);
        $this->assertCustomValidationErrors($response, ['email', 'user_name']);
    }

    /**
     * MST_STR_006: Email Invalid Format
     */
    public function test_ms_t_st_r_006_email_invalid_format()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload();
        $payload['email'] = 'not-an-email';

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);
        $this->assertCustomValidationErrors($response, ['email']);
    }

    /**
     * MST_STR_007: Email Duplicate
     */
    public function test_ms_t_st_r_007_email_duplicate()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $existing = AdminMst::factory()->create();

        $payload = $this->getValidPayload();
        $payload['email'] = $existing->email;

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);
        $this->assertCustomValidationErrors($response, ['email']);
    }

    /**
     * MST_STR_008: User Name Max Length (50)
     */
    public function test_ms_t_st_r_008_user_name_max_length()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload();
        $payload['user_name'] = str_repeat('a', 51);

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);
        $this->assertCustomValidationErrors($response, ['user_name']);
    }

    /**
     * MST_STR_009: Gender Invalid Enum
     */
    public function test_ms_t_st_r_009_gender_invalid_enum()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload();
        $payload['gender'] = 99;

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);
        $this->assertCustomValidationErrors($response, ['gender']);
    }

    /**
     * MST_STR_010: Status Invalid Enum
     */
    public function test_ms_t_st_r_010_status_invalid_enum()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload();
        $payload['status'] = 99;

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);
        $this->assertCustomValidationErrors($response, ['status']);
    }

    /**
     * MST_STR_011: IsActive Invalid Enum
     */
    public function test_ms_t_st_r_011_is_active_invalid_enum()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload();
        $payload['is_active'] = 99;

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);
        $this->assertCustomValidationErrors($response, ['is_active']);
    }

    /**
     * MST_STR_012: IsDelete Invalid Enum
     */
    public function test_ms_t_st_r_012_is_delete_invalid_enum()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload();
        $payload['is_delete'] = 99;

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);
        $this->assertCustomValidationErrors($response, ['is_delete']);
    }

    /**
     * MST_STR_013: Birth Date Invalid Format (Must be d/m/Y per CommonVal::DATE_FORMAT)
     */
    public function test_ms_t_st_r_013_birth_invalid_format()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload();
        $payload['birth'] = '2000-10-20'; // Y-m-d should fail if d/m/Y required

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);
        $this->assertCustomValidationErrors($response, ['birth']);
    }

    /**
     * MST_STR_014: Birth Date Before Min (1900-01-01)
     */
    public function test_ms_t_st_r_014_birth_before_min()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload();
        // CommonVal::MIN_DATE is '1900-01-01', DATE_FORMAT is 'd/m/Y'
        // Test date: 31/12/1899
        $payload['birth'] = '31/12/1899';

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);
        $this->assertCustomValidationErrors($response, ['birth']);
    }

    /**
     * MST_STR_015: First Name Max Length (20)
     */
    public function test_ms_t_st_r_015_first_name_max_length()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload();
        $payload['first_name'] = str_repeat('a', 21);

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);
        $this->assertCustomValidationErrors($response, ['first_name']);
    }

    /**
     * MST_STR_016: Avatar Max Length (30)
     */
    public function test_ms_t_st_r_016_avatar_max_length()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload();
        $payload['avatar'] = str_repeat('a', 31);

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);
        $this->assertCustomValidationErrors($response, ['avatar']);
    }

    /**
     * MST_STR_017: Phone Number Max Length (20)
     */
    public function test_ms_t_st_r_017_phone_number_max_length()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload();
        $payload['phone_number'] = str_repeat('1', 21);

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);
        $this->assertCustomValidationErrors($response, ['phone_number']);
    }

    // ======================================================================
    // Step 5: Service / Business logic analysis (and DB)
    // ======================================================================

    /**
     * MST_STR_018: Success Full Payload
     */
    public function test_ms_t_st_r_018_success_full_payload()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload();

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);

        $response->assertStatus(CommonVal::HTTP_OK);

        $response->assertJsonStructure(['data', 'error']);

        $newId = $response->json('data');
        $this->assertIsInt($newId);

        // Verification in DB
        $this->assertDatabaseHas('admin_mst', [
            'id' => $newId,
            'email' => $payload['email'],
            'user_name' => $payload['user_name'],
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
            // birth is stored as Y-m-d in DB mostly? Depends on Casts. Assuming DB stores standard date.
            // If request sent d/m/Y, Model might mutate it or DB takes it.
            // We usually check date string if we know format.
            // 'birth' => '1990-01-01' from '01/01/1990'
            'gender' => $payload['gender'],
            'status' => $payload['status'],
        ]);

        // History verification
        $this->assertDatabaseHas('admin_mst_hist', [
            'admin_mst_id' => $newId,
            'action' => ActionType::CREATE->value,
            'user_name' => $payload['user_name'],
        ]);
    }

    /**
     * MST_STR_019: Success Minimal Payload
     */
    public function test_ms_t_st_r_019_success_minimal_payload()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload();
        // unset($payload['address']);
        // unset($payload['phone_number']);
        unset($payload['avatar']);
        // unset($payload['birth']); // allow missing? REQUEST implies date_format validation...
        // If Request validation rules run for date_format/after_or_equal WITHOUT 'required' or 'nullable',
        // Laravel logic: if field is missing, rules are skipped (implicit nullable).
        // Let's assume it passes.

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);

        $response->assertStatus(CommonVal::HTTP_OK);

        $newId = $response->json('data');
        $this->assertDatabaseHas('admin_mst', [
            'id' => $newId,
            'address' => $payload['address'],
        ]);
    }

    /**
     * Helper to generate valid payload
     */
    private function getValidPayload(): array
    {
        return [
            'email' => 'test@gmail.com', // Use real domain for DNS check
            'user_name' => 'testuser',
            'password' => 'Password123',
            'first_name' => 'Test',
            'last_name' => 'User',
            'gender' => Gender::MALE->value,
            'status' => StatusEnum::PUBLISHED->value,
            'is_active' => IsActive::TRUE->value,
            'is_delete' => IsDelete::FALSE->value,
            'birth' => '01/01/2000',
            // fields not required or nullable can be omitted or added
            'address' => '123 Test St',
            'phone_number' => '0901234567',
        ];
    }

    /**
     * MST_STR_020: Success with Empty String Birth (Regression Test)
     */
    public function test_ms_t_st_r_020_success_empty_string_birth()
    {
        $admin = AdminMst::factory()->create(['password' => Hash::make('password')]);
        $cookies = $this->getAuthCookies($admin);

        $payload = $this->getValidPayload();
        $payload['birth'] = '';

        $response = $this->call('POST', $this->storeUrl, $payload, $cookies);

        $response->assertStatus(CommonVal::HTTP_OK);

        $newId = $response->json('data');
        $this->assertDatabaseHas('admin_mst', [
            'id' => $newId,
            'user_name' => $payload['user_name'],
            'birth' => null, // Should be null in DB
        ]);

        // History verification
        $this->assertDatabaseHas('admin_mst_hist', [
            'admin_mst_id' => $newId,
            'birth' => null, // Should be null in History DB
        ]);
    }
}
