<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Models\Audit\AuditLog;
use App\Models\Master\AdminMst;
use App\Models\Master\Department;
use App\Models\Media\MediaMgmt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\UploadsFiles;
use Tests\TestCase;

/**
 * Folders given to departments (ADR-0017): a locked folder and everything inside it is hidden from
 * admins outside its departments, everywhere the file manager reaches.
 */
final class FolderPolicyApiTest extends TestCase
{
    use AuthenticatesAdmins;
    use UploadsFiles;

    private const BASE = '/api/admin/media';

    /** @var array<string, string> */
    private array $owner;

    private Department $sales;

    private Department $legal;

    private int $secret;

    private int $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeStorage();
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
        [$this->sales, $this->legal] = Department::factory()->count(2)->create()->all();

        $this->secret = $this->folder('Secret');
        $this->plan = $this->file('/Secret', 'plan.txt');
        $this->folder('Open');
        $this->call('PUT', self::BASE."/access/{$this->secret}", ['department_ids' => [$this->sales->id]], $this->owner)->assertOk();
    }

    public function test_locking_a_folder_is_listed_and_audited(): void
    {
        $this->call('GET', self::BASE.'/list', ['name' => 'Secret'], $this->owner)->assertOk()
            ->assertJsonPath('data.data.0.department_ids', [$this->sales->id]);
        $audit = AuditLog::query()->where(['auditable_type' => 'media', 'auditable_id' => $this->secret, 'event' => 'updated'])->firstOrFail();
        $this->assertSame([], $audit->old_values['department_ids']);
        $this->assertSame([$this->sales->id], $audit->new_values['department_ids']);

        $this->call('PUT', self::BASE."/access/{$this->plan}", ['department_ids' => [$this->sales->id]], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonPath('error.messages.id.0', Messages::E0045);
        $this->call('PUT', self::BASE."/access/{$this->secret}", ['department_ids' => [999999]], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('PUT', self::BASE."/access/{$this->secret}", [], $this->owner)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('PUT', self::BASE."/access/{$this->secret}", ['department_ids' => []], $this->loginAs(AdminMst::factory()->create()))
            ->assertStatus(CommonVal::HTTP_FORBIDDEN);

        // An empty list opens the folder again
        $this->call('PUT', self::BASE."/access/{$this->secret}", ['department_ids' => []], $this->owner)->assertOk();
        $this->call('GET', self::BASE.'/list', [], $this->filesUser())->assertOk()->assertJsonCount(2, 'data.data');
    }

    public function test_an_admin_outside_the_departments_cannot_reach_the_folder_or_its_files(): void
    {
        $outsider = $this->filesUser($this->legal);
        $open = $this->file('/Open', 'notes.txt');

        $this->call('GET', self::BASE.'/list', [], $outsider)->assertOk()
            ->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.name', 'Open');
        $this->call('GET', self::BASE.'/list', ['folder' => '/Secret'], $outsider)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('GET', self::BASE."/download-url/{$this->plan}", [], $outsider)->assertStatus(CommonVal::HTTP_NOT_FOUND);
        $this->call('POST', self::BASE.'/upload-url', ['folder' => '/Secret', 'name' => 'x.txt', 'size' => 1, 'mime_type' => 'text/plain'], $outsider)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('POST', self::BASE.'/multipart/start', ['folder' => '/Secret', 'name' => 'big.bin', 'size' => 50 * 1024 * 1024, 'mime_type' => 'application/octet-stream'], $outsider)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('POST', self::BASE.'/store-folder', ['folder' => '/Secret', 'name' => 'Sub'], $outsider)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('PUT', self::BASE."/update/{$this->plan}", ['name' => 'mine.txt'], $outsider)->assertStatus(CommonVal::HTTP_NOT_FOUND);
        $this->call('PUT', self::BASE."/update/{$this->secret}", ['name' => 'Mine'], $outsider)->assertStatus(CommonVal::HTTP_NOT_FOUND);
        $this->call('PUT', self::BASE."/update/{$open}", ['folder' => '/Secret'], $outsider)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        // Skipped like a missing id
        $this->call('POST', self::BASE.'/delete', ['ids' => [$this->plan]], $outsider)->assertOk();
        $this->call('PUT', self::BASE."/access/{$this->secret}", ['department_ids' => [$this->legal->id]], $outsider)->assertStatus(CommonVal::HTTP_NOT_FOUND);

        $this->assertDatabaseHas('media_mgmt', ['id' => $this->plan, 'virtual_path' => '/Secret/plan.txt', 'is_delete' => false]);

        // No department at all is no way around the lock; an inactive department grants nothing
        $this->call('GET', self::BASE.'/list', [], $this->filesUser())->assertOk()->assertJsonCount(1, 'data.data');
        $this->sales->update(['is_active' => false]);
        $this->call('GET', self::BASE.'/list', [], $this->filesUser($this->sales))->assertOk()->assertJsonCount(1, 'data.data');
    }

    public function test_a_member_uses_the_folder_like_before(): void
    {
        $member = $this->filesUser($this->sales);

        $this->call('GET', self::BASE.'/list', [], $member)->assertOk()->assertJsonCount(2, 'data.data');
        $this->call('GET', self::BASE.'/list', ['folder' => '/Secret'], $member)->assertOk()->assertJsonPath('data.data.0.id', $this->plan);
        $this->call('GET', self::BASE."/download-url/{$this->plan}", [], $member)->assertOk();
        $this->file('/Secret', 'budget.txt', $member);
        $this->call('PUT', self::BASE."/update/{$this->plan}", ['name' => 'plan-v2.txt'], $member)->assertOk();
        $this->call('POST', self::BASE.'/delete', ['ids' => [$this->plan]], $member)->assertOk();
    }

    public function test_the_admin_search_finds_a_locked_folder_and_its_files_only_for_members(): void
    {
        foreach (['secret', 'plan'] as $q) {
            $this->call('GET', '/api/admin/search', ['q' => $q], $this->filesUser($this->legal))->assertOk()->assertJsonPath('data.files', []);
            $this->call('GET', '/api/admin/search', ['q' => $q], $this->filesUser())->assertOk()->assertJsonPath('data.files', []);
        }
        $this->call('GET', '/api/admin/search', ['q' => 'plan'], $this->filesUser($this->sales))->assertOk()
            ->assertJsonPath('data.files.0.id', $this->plan);
        $this->call('GET', '/api/admin/search', ['q' => 'secret'], $this->filesUser($this->sales))->assertOk()
            ->assertJsonPath('data.files.0.id', $this->secret);
    }

    public function test_a_lock_inside_a_lock_needs_both_and_survives_a_rename(): void
    {
        $inner = $this->folder('Inner', '/Secret');
        $this->call('PUT', self::BASE."/access/{$inner}", ['department_ids' => [$this->legal->id]], $this->owner)->assertOk();
        $salesOnly = $this->filesUser($this->sales);
        $legalOnly = $this->filesUser($this->legal);
        $both = $this->filesUser($this->sales, $this->legal);

        $this->call('GET', self::BASE.'/list', ['folder' => '/Secret'], $salesOnly)->assertOk()->assertJsonCount(1, 'data.data');
        $this->call('GET', self::BASE.'/list', ['folder' => '/Secret/Inner'], $salesOnly)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('GET', self::BASE.'/list', ['folder' => '/Secret/Inner'], $legalOnly)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('GET', self::BASE.'/list', ['folder' => '/Secret/Inner'], $both)->assertOk();

        // A member cannot delete a folder holding one hidden from them
        $this->call('POST', self::BASE.'/delete', ['ids' => [$this->secret]], $salesOnly)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonPath('error.messages.ids.0', Messages::E0044);

        // The lock follows the folder by id
        $this->call('PUT', self::BASE."/update/{$this->secret}", ['name' => 'Hidden'], $this->owner)->assertOk();
        $this->call('GET', self::BASE.'/list', ['folder' => '/Hidden'], $legalOnly)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('GET', self::BASE.'/list', ['folder' => '/Hidden/Inner'], $both)->assertOk();
    }

    public function test_the_trash_shows_only_files_of_allowed_folders_even_after_the_folder_is_deleted(): void
    {
        $outsider = $this->filesUser($this->legal);
        $member = $this->filesUser($this->sales);
        $this->call('POST', self::BASE.'/delete', ['ids' => [$this->plan]], $this->owner)->assertOk();

        $this->call('GET', self::BASE.'/trash/list', [], $outsider)->assertOk()->assertJsonCount(0, 'data.data');
        $this->call('POST', self::BASE."/trash/restore/{$this->plan}", [], $outsider)->assertStatus(CommonVal::HTTP_NOT_FOUND);
        $this->call('POST', self::BASE.'/trash/delete', ['ids' => [$this->plan]], $outsider)->assertOk();
        $this->call('POST', self::BASE.'/trash/empty', [], $outsider)->assertOk();
        $this->assertDatabaseHas('media_mgmt', ['id' => $this->plan]);

        $this->call('POST', self::BASE.'/delete', ['ids' => [$this->secret]], $this->owner)->assertOk();
        $this->call('GET', self::BASE.'/trash/list', [], $outsider)->assertOk()->assertJsonCount(0, 'data.data');
        $this->call('GET', self::BASE.'/trash/list', [], $member)->assertOk()->assertJsonPath('data.data.0.id', $this->plan);
    }

    public function test_an_unfinished_upload_stops_when_its_folder_is_locked(): void
    {
        $uploader = $this->filesUser($this->legal);
        $this->folder('Later');
        $id = $this->call('POST', self::BASE.'/multipart/start', ['folder' => '/Later', 'name' => 'big.bin', 'size' => 50 * 1024 * 1024, 'mime_type' => 'application/octet-stream'], $uploader)
            ->assertOk()->json('data.id');
        $this->call('GET', self::BASE.'/multipart/unfinished', [], $uploader)->assertOk()->assertJsonCount(1, 'data');

        $later = (int) MediaMgmt::query()->where('virtual_path', '/Later')->value('id');
        $this->call('PUT', self::BASE."/access/{$later}", ['department_ids' => [$this->sales->id]], $this->owner)->assertOk();

        $this->call('GET', self::BASE.'/multipart/unfinished', [], $uploader)->assertOk()->assertJsonCount(0, 'data');
        $this->call('POST', self::BASE."/multipart/resume/{$id}", [], $uploader)->assertStatus(CommonVal::HTTP_NOT_FOUND);
        $this->call('POST', self::BASE."/multipart/part-urls/{$id}", ['part_numbers' => [1]], $uploader)->assertStatus(CommonVal::HTTP_NOT_FOUND);
    }

    public function test_a_category_attaches_only_allowed_files_and_shows_locked_ones_by_name(): void
    {
        $outsider = $this->filesUser($this->legal);
        $payload = ['name' => 'Kế hoạch', 'attachments' => [['title' => 'Plan', 'media_id' => $this->plan]]];

        $this->call('POST', '/api/admin/content-category/store', $payload, $outsider)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonPath('error.messages.attachments.0', Messages::E0041);
        $id = $this->call('POST', '/api/admin/content-category/store', $payload, $this->filesUser($this->sales))->assertOk()->json('data');

        $this->call('GET', '/api/admin/content-category/list', ['id' => $id], $outsider)->assertOk()
            ->assertJsonPath('data.data.0.attachments.0.file.name', 'plan.txt')
            ->assertJsonPath('data.data.0.attachments.0.file.locked', true)
            ->assertJsonPath('data.data.0.attachments.0.file.preview', null);
        $this->call('GET', '/api/admin/content-category/list', ['id' => $id], $this->owner)->assertOk()
            ->assertJsonPath('data.data.0.attachments.0.file.locked', false)
            ->assertJsonPath('data.data.0.attachments.0.file.preview', 'text');

        // Keeping a file already attached is fine, even for an admin who cannot open it
        $this->call('PUT', "/api/admin/content-category/update/{$id}", $payload, $outsider)->assertOk();
    }

    /**
     * An admin who may use every Files and category API and the search, in the given departments.
     *
     * @return array<string, string>
     */
    private function filesUser(Department ...$departments): array
    {
        $role = Role::findOrCreate('Files user', CommonVal::ADMIN_GUARD);
        $role->syncPermissions(Permission::query()->where('guard_name', CommonVal::ADMIN_GUARD)
            ->where(fn ($q) => $q->where('name', 'like', 'media.%')->orWhere('name', 'like', 'content-category.%')->orWhere('name', 'search.query'))
            ->pluck('name'));
        $admin = AdminMst::factory()->create();
        $admin->syncRoles([$role]);
        $admin->departments()->sync(array_map(fn (Department $department): int => $department->id, $departments));

        return $this->loginAs($admin);
    }

    private function folder(string $name, string $parent = '/'): int
    {
        return (int) $this->call('POST', self::BASE.'/store-folder', ['folder' => $parent, 'name' => $name], $this->owner)->assertOk()->json('data');
    }

    /**
     * @param  array<string, string>|null  $cookies
     */
    private function file(string $folder, string $name, ?array $cookies = null): int
    {
        $cookies ??= $this->owner;
        $id = $this->call('POST', self::BASE.'/upload-url', ['folder' => $folder, 'name' => $name, 'size' => 10, 'mime_type' => 'text/plain'], $cookies)
            ->assertOk()->json('data.id');
        $this->storage->put((string) MediaMgmt::query()->findOrFail($id)->minio_object_key, 10);
        $this->call('POST', self::BASE."/complete/{$id}", [], $cookies)->assertOk();

        return (int) $id;
    }
}
