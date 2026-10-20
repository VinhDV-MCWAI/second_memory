<?php

declare(strict_types=1);

namespace Tests\Feature\Master;

use App\Constants\CommonVal;
use App\Enums\AdminRole;
use App\Models\Audit\AuditLog;
use App\Models\Master\AdminMst;
use Spatie\Permission\Models\Role;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * Role management of the dynamic permissions (ADR-0013).
 */
final class RoleApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const BASE = '/api/admin/role';

    /** @var array<string, string> */
    private array $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
    }

    public function test_crud_with_permissions_and_audit(): void
    {
        $id = $this->call('POST', self::BASE.'/store', ['name' => 'Article editor', 'permissions' => ['article.list', 'article.update']], $this->owner)
            ->assertOk()->json('data');
        $this->call('POST', self::BASE.'/store', ['name' => 'ARTICLE EDITOR'], $this->owner)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);

        $this->call('PUT', self::BASE."/update/{$id}", ['name' => 'Article editor', 'permissions' => ['article.list']], $this->owner)->assertOk();

        $this->call('GET', self::BASE.'/list', ['name' => 'article'], $this->owner)
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Article editor')
            ->assertJsonPath('data.data.0.permissions', ['article.list'])
            ->assertJsonPath('data.data.0.is_built_in', false)
            ->assertJsonPath('data.data.0.admin_count', 0);

        $updated = AuditLog::query()->where(['auditable_type' => 'role', 'auditable_id' => $id])->latest('id')->firstOrFail();
        $this->assertSame(['article.list', 'article.update'], $updated->old_values['permissions']);
        $this->assertSame(['article.list'], $updated->new_values['permissions']);

        $this->call('POST', self::BASE.'/delete', ['ids' => [$id]], $this->owner)->assertOk();
        $this->assertDatabaseMissing('roles', ['id' => $id]);
    }

    public function test_unknown_permissions_are_rejected(): void
    {
        $this->call('POST', self::BASE.'/store', ['name' => 'Broken', 'permissions' => ['nope.list']], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['permissions.0']]]);
    }

    public function test_built_in_roles_keep_their_name_and_cannot_be_deleted(): void
    {
        $viewer = $this->roleId(AdminRole::VIEWER);
        $owner = $this->roleId(AdminRole::OWNER);

        $this->call('PUT', self::BASE."/update/{$viewer}", ['name' => 'Reader'], $this->owner)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('PUT', self::BASE."/update/{$owner}", ['name' => AdminRole::OWNER->value, 'permissions' => ['article.list']], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('POST', self::BASE.'/delete', ['ids' => [$viewer]], $this->owner)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);

        // The viewer role's permissions can still be changed
        $this->call('PUT', self::BASE."/update/{$viewer}", ['name' => AdminRole::VIEWER->value, 'permissions' => ['article.list']], $this->owner)->assertOk();
    }

    public function test_a_role_held_by_an_admin_cannot_be_deleted(): void
    {
        $role = Role::create(['name' => 'Tagger', 'guard_name' => CommonVal::ADMIN_GUARD]);
        AdminMst::factory()->create()->assignRole($role);

        $this->call('POST', self::BASE.'/delete', ['ids' => [$role->id]], $this->owner)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    }

    public function test_permissions_are_listed_by_feature(): void
    {
        $groups = collect($this->call('GET', '/api/admin/permission/list', [], $this->owner)->assertOk()->json('data'));

        $article = $groups->firstWhere('feature', 'article');
        $this->assertContains(['name' => 'article.list', 'action' => 'list', 'method' => 'GET'], $article['permissions']);
        $this->assertNotNull($groups->firstWhere('feature', 'role'));
    }

    public function test_a_viewer_reads_roles_but_cannot_change_them(): void
    {
        $viewer = $this->loginAs(AdminMst::factory()->create());

        $this->call('GET', self::BASE.'/list', [], $viewer)->assertOk();
        $this->call('GET', '/api/admin/permission/list', [], $viewer)->assertOk();
        $this->call('POST', self::BASE.'/store', ['name' => 'Mine'], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    private function roleId(AdminRole $role): int
    {
        return (int) Role::findByName($role->value, CommonVal::ADMIN_GUARD)->id;
    }
}
