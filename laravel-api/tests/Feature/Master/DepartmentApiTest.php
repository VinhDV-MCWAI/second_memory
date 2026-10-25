<?php

declare(strict_types=1);

namespace Tests\Feature\Master;

use App\Constants\CommonVal;
use App\Models\Audit\AuditLog;
use App\Models\Master\AdminMst;
use App\Models\Master\Department;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * Departments and their members (ADR-0017).
 */
final class DepartmentApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const BASE = '/api/admin/department';

    private AdminMst $ownerAdmin;

    /** @var array<string, string> */
    private array $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ownerAdmin = AdminMst::factory()->create();
        $this->owner = $this->loginAsOwner($this->ownerAdmin);
    }

    public function test_crud_with_case_insensitive_codes_and_audit(): void
    {
        $payload = ['code' => 'HR', 'name' => 'Human resources', 'description' => 'People', 'is_active' => 1];
        $id = $this->call('POST', self::BASE.'/store', $payload, $this->owner)->assertOk()->json('data');
        $this->call('POST', self::BASE.'/store', [...$payload, 'code' => 'hr'], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['code']]]);

        $this->call('PUT', self::BASE."/update/{$id}", [...$payload, 'code' => 'hr', 'is_active' => 0], $this->owner)->assertOk();

        $this->call('GET', self::BASE.'/list', ['code' => 'H', 'is_active' => 0], $this->owner)
            ->assertOk()
            ->assertJsonPath('data.data.0.code', 'hr')
            ->assertJsonPath('data.data.0.name', 'Human resources')
            ->assertJsonPath('data.data.0.is_active', false)
            ->assertJsonPath('data.data.0.admin_count', 0);
        $this->assertDatabaseHas('department', ['id' => $id, 'created_by' => $this->ownerAdmin->id, 'updated_by' => $this->ownerAdmin->id]);

        $updated = AuditLog::query()->where(['auditable_type' => 'department', 'auditable_id' => $id])->latest('id')->firstOrFail();
        $this->assertSame(['code' => 'HR', 'is_active' => true], $updated->old_values);
        $this->assertSame(['code' => 'hr', 'is_active' => false], $updated->new_values);

        $this->call('POST', self::BASE.'/delete', ['ids' => [$id]], $this->owner)->assertOk();
        $this->assertDatabaseMissing('department', ['id' => $id]);
        $this->assertSame(3, AuditLog::query()->where(['auditable_type' => 'department', 'auditable_id' => $id])->count());
    }

    public function test_required_fields_and_limits(): void
    {
        $this->call('POST', self::BASE.'/store', ['code' => str_repeat('x', 31), 'is_active' => 5], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['code', 'name', 'is_active']]]);
    }

    public function test_deleting_a_department_with_members_keeps_the_admins(): void
    {
        $department = Department::factory()->create();
        $member = AdminMst::factory()->create();
        $member->departments()->attach($department->id);

        $this->call('GET', self::BASE.'/list', ['id' => $department->id], $this->owner)->assertJsonPath('data.data.0.admin_count', 1);
        $this->call('POST', self::BASE.'/delete', ['ids' => [$department->id]], $this->owner)->assertOk();

        $this->assertDatabaseMissing('admin_department', ['department_id' => $department->id]);
        $this->assertDatabaseHas('admin_mst', ['id' => $member->id, 'is_delete' => false]);
    }

    public function test_unknown_ids_are_rejected(): void
    {
        $this->call('POST', self::BASE.'/delete', ['ids' => [999999]], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('PUT', self::BASE.'/update/999999', ['code' => 'X', 'name' => 'X', 'is_active' => 1], $this->owner)
            ->assertStatus(CommonVal::HTTP_NOT_FOUND);
    }

    public function test_equal_sort_values_keep_one_order_across_pages(): void
    {
        Department::query()->delete();
        $ids = Department::factory()->count(3)->create(['updated_at' => '2026-10-01 10:00:00'])->modelKeys();

        foreach (['asc', 'desc'] as $order) {
            $seen = [];
            foreach ([1, 2, 3] as $page) {
                $seen[] = $this->call('GET', self::BASE.'/list', ['sort_by' => 'updated_at', 'sort_order' => $order, 'per_page' => 1, 'page' => $page], $this->owner)
                    ->json('data.data.0.id');
            }
            sort($seen);
            $this->assertSame($ids, $seen, "every row once when sorted {$order}");
        }
    }

    public function test_viewer_can_list_but_not_write(): void
    {
        Department::factory()->create();
        $viewer = $this->loginAs(AdminMst::factory()->create());

        $this->call('GET', self::BASE.'/list', [], $viewer)->assertOk();
        $this->call('POST', self::BASE.'/store', ['code' => 'IT', 'name' => 'IT', 'is_active' => 1], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    public function test_guest_is_refused(): void
    {
        $this->call('GET', self::BASE.'/list')->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_admins_join_several_departments(): void
    {
        [$hr, $it] = Department::factory()->count(2)->create()->all();
        $admin = AdminMst::factory()->create();
        $base = '/api/admin/admin-mst';

        $listed = $this->call('GET', $base.'/list', ['id' => $admin->id], $this->owner)->json('data.data.0');
        $payload = [
            ...collect($listed)->only(['email', 'user_name', 'first_name', 'last_name', 'gender', 'status'])->all(),
            'is_active' => 1,
            'department_ids' => [$hr->id, $it->id],
        ];
        $this->call('PUT', $base."/update/{$admin->id}", $payload, $this->owner)->assertOk();

        $this->call('GET', $base.'/list', ['department_id' => $it->id], $this->owner)
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $admin->id)
            ->assertJsonPath('data.data.0.departments.*.id', [$hr->id, $it->id]);
        $audit = AuditLog::query()->where(['auditable_type' => 'admin', 'auditable_id' => $admin->id])->latest('id')->firstOrFail();
        $this->assertSame([], $audit->old_values['departments']);

        // Leaving department_ids out keeps the memberships; an empty list removes them
        $this->call('PUT', $base."/update/{$admin->id}", [...$payload, 'department_ids' => null], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('PUT', $base."/update/{$admin->id}", collect($payload)->except('department_ids')->all(), $this->owner)->assertOk();
        $this->assertCount(2, $admin->fresh()->departments);
        $this->call('PUT', $base."/update/{$admin->id}", [...$payload, 'department_ids' => []], $this->owner)->assertOk();
        $this->assertCount(0, $admin->fresh()->departments);

        $this->call('PUT', $base."/update/{$admin->id}", [...$payload, 'department_ids' => [999999]], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['department_ids.0']]]);
    }
}
