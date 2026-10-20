<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Constants\CommonVal;
use App\Enums\AdminRole;
use App\Models\Master\AdminMst;
use App\Support\Permissions\AdminRoutePermissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * ADR-0013: every admin route is a permission, granted through roles; the owner role passes every check.
 */
final class DynamicPermissionTest extends TestCase
{
    use AuthenticatesAdmins;

    public function test_every_admin_route_is_named_and_registered(): void
    {
        $permissions = app(AdminRoutePermissions::class);

        $this->assertSame([], $permissions->unnamed());
        $this->assertSame([], $permissions->sync()['added'], 'run make permissions after adding a route');
        $this->assertArrayHasKey('article.list', $permissions->all());
        $this->assertArrayNotHasKey('credential.me', $permissions->all());
    }

    public function test_a_custom_role_opens_exactly_its_permissions(): void
    {
        Role::create(['name' => 'article-reader', 'guard_name' => CommonVal::ADMIN_GUARD])->givePermissionTo('article.list');
        $admin = AdminMst::factory()->create();
        $admin->syncRoles(['article-reader']);
        $cookies = $this->loginAs($admin);

        $this->call('GET', '/api/admin/article/list', [], $cookies)->assertOk();
        $this->call('GET', '/api/admin/credential/me', [], $cookies)->assertOk();
        $this->call('GET', '/api/admin/content-category/list', [], $cookies)->assertStatus(CommonVal::HTTP_FORBIDDEN);
        $this->call('POST', '/api/admin/content-category/store', ['name' => 'Go'], $cookies)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    public function test_an_admin_without_roles_only_reaches_the_always_allowed_routes(): void
    {
        $admin = AdminMst::factory()->create();
        $admin->syncRoles([]);
        $cookies = $this->loginAs($admin);

        $this->call('GET', '/api/admin/credential/me', [], $cookies)->assertOk();
        $this->call('GET', '/api/admin/content-category/list', [], $cookies)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    public function test_the_viewer_role_reads_everything_and_writes_nothing(): void
    {
        $viewer = Role::findByName(AdminRole::VIEWER->value, CommonVal::ADMIN_GUARD);
        $reads = array_keys(array_filter(app(AdminRoutePermissions::class)->all(), fn (string $method): bool => $method === 'GET'));

        $this->assertEqualsCanonicalizing($reads, $viewer->permissions->pluck('name')->all());
    }

    public function test_the_owner_role_holds_no_permission_yet_passes_every_check(): void
    {
        $this->assertSame(0, Role::findByName(AdminRole::OWNER->value, CommonVal::ADMIN_GUARD)->permissions()->count());
        $cookies = $this->loginAsOwner(AdminMst::factory()->create());

        $this->call('POST', '/api/admin/content-category/store', ['name' => 'Go'], $cookies)->assertOk();
    }

    public function test_me_lists_the_permissions_the_ui_may_show(): void
    {
        $viewer = $this->call('GET', '/api/admin/credential/me', [], $this->loginAs(AdminMst::factory()->create()))->assertOk();
        $this->assertContains('article.list', $viewer->json('data.permissions'));
        $this->assertNotContains('article.store', $viewer->json('data.permissions'));
        $viewer->assertJsonPath('data.roles.0.name', AdminRole::VIEWER->value);

        $owner = $this->call('GET', '/api/admin/credential/me', [], $this->loginAsOwner(AdminMst::factory()->create()))->assertOk();
        $this->assertEqualsCanonicalizing(array_keys(app(AdminRoutePermissions::class)->all()), $owner->json('data.permissions'));
    }

    public function test_the_sync_command_adds_missing_and_prunes_stale_permissions(): void
    {
        Permission::query()->where('name', 'article.list')->delete();
        Permission::create(['name' => 'gone.list', 'guard_name' => CommonVal::ADMIN_GUARD]);

        $this->artisan('permission:sync-routes')->expectsOutputToContain('Added: article.list')->assertSuccessful();
        $this->assertDatabaseHas('permissions', ['name' => 'gone.list']);

        $this->artisan('permission:sync-routes --prune')->expectsOutputToContain('Added: none')->assertSuccessful();
        $this->assertDatabaseMissing('permissions', ['name' => 'gone.list']);
    }
}
