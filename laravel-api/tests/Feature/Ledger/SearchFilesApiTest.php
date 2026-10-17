<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Constants\CommonVal;
use App\Models\Master\AdminMst;
use Spatie\Permission\Models\Role;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\UploadsFiles;
use Tests\TestCase;

/**
 * SEARCH-01: the admin search also finds files and folders by name. Locked folders: FolderPolicyApiTest.
 */
final class SearchFilesApiTest extends TestCase
{
    use AuthenticatesAdmins;
    use UploadsFiles;

    private const URL = '/api/admin/search';

    /** @var array<string, string> */
    private array $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeStorage();
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
    }

    public function test_files_and_folders_are_found_by_name_without_accents_but_not_in_the_trash(): void
    {
        $folder = (int) $this->call('POST', '/api/admin/media/store-folder', ['folder' => '/', 'name' => 'Hợp đồng'], $this->owner)->assertOk()->json('data');
        $contract = $this->uploadFile($this->owner, 'hợp-đồng-thuê-nhà.pdf', 2048, '/Hợp đồng');
        $this->uploadFile($this->owner, 'notes.txt');
        $trashed = $this->uploadFile($this->owner, 'hop dong cu.pdf');
        $this->call('POST', '/api/admin/media/delete', ['ids' => [$trashed]], $this->owner)->assertOk();

        $this->call('GET', self::URL, ['q' => 'hop dong'], $this->owner)->assertOk()
            ->assertJsonPath('data.match', 'exact')
            // Shortest name first
            ->assertJsonPath('data.files', [
                ['id' => $folder, 'is_file' => false, 'name' => 'Hợp đồng', 'path' => '/Hợp đồng', 'size' => null, 'thumbnail_url' => null],
                ['id' => $contract, 'is_file' => true, 'name' => 'hợp-đồng-thuê-nhà.pdf', 'path' => '/Hợp đồng/hợp-đồng-thuê-nhà.pdf', 'size' => 2048, 'thumbnail_url' => null],
            ]);
        // Every word must be in the name, in any order
        $this->call('GET', self::URL, ['q' => 'nha thue'], $this->owner)->assertOk()
            ->assertJsonCount(1, 'data.files')->assertJsonPath('data.files.0.id', $contract);
    }

    public function test_a_typo_in_a_file_name_falls_back_to_fuzzy_matching(): void
    {
        $id = $this->uploadFile($this->owner, 'kubernetes-cheatsheet.pdf');

        $this->call('GET', self::URL, ['q' => 'kubernets cheatsheet'], $this->owner)->assertOk()
            ->assertJsonPath('data.match', 'fuzzy')
            ->assertJsonPath('data.files.0.id', $id);
    }

    public function test_an_admin_who_may_not_see_files_gets_no_files(): void
    {
        $this->uploadFile($this->owner, 'roadmap.pdf');
        $role = Role::findOrCreate('Searcher', CommonVal::ADMIN_GUARD);
        $role->syncPermissions(['search.query']);
        $admin = AdminMst::factory()->create();
        $admin->syncRoles([$role]);

        $this->call('GET', self::URL, ['q' => 'roadmap'], $this->loginAs($admin))->assertOk()
            ->assertJsonPath('data.files', []);
        $this->call('GET', self::URL, ['q' => 'roadmap'], $this->owner)->assertOk()
            ->assertJsonCount(1, 'data.files');
    }
}
