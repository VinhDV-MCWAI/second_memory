<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Constants\CommonVal;
use App\Constants\MediaConst;
use App\Constants\Messages;
use App\Enums\AuditEvent;
use App\Models\Audit\AuditLog;
use App\Models\Master\AdminMst;
use App\Models\Media\MediaMgmt;
use App\Services\Media\ObjectStorage;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Fakes\FakeObjectStorage;
use Tests\TestCase;

/**
 * Trash (MEDIA-07): deleted files are listed for 30 days, restored into their folder, or deleted for good.
 */
final class MediaTrashApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const BASE = '/api/admin/media';

    /** @var array<string, string> */
    private array $owner;

    private AdminMst $ownerAdmin;

    private FakeObjectStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = new FakeObjectStorage;
        $this->app->instance(ObjectStorage::class, $this->storage);
        $this->ownerAdmin = AdminMst::factory()->create();
        $this->owner = $this->loginAsOwner($this->ownerAdmin);
    }

    public function test_a_deleted_file_is_listed_and_restored_into_its_folder(): void
    {
        $this->folder('Docs');
        $id = $this->file('/Docs', 'report.pdf');
        $this->deleteItems([$id]);

        $this->call('GET', self::BASE.'/list', ['folder' => '/Docs'], $this->owner)->assertOk()->assertJsonCount(0, 'data.data');
        $this->call('GET', self::BASE.'/trash/list', [], $this->owner)->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $id)
            ->assertJsonPath('data.data.0.path', '/Docs/report.pdf')
            ->assertJsonPath('data.data.0.deleted_by', $this->ownerAdmin->user_name)
            ->assertJsonPath('data.data.0.expires_at', now()->addDays(MediaConst::TRASH_DAYS)->startOfSecond()->toIso8601String());

        $this->call('POST', self::BASE."/trash/restore/{$id}", [], $this->owner)->assertOk()
            ->assertJsonPath('data.data.path', '/Docs/report.pdf');

        $this->assertNotNull($this->storage->head($this->keyOf($id)));
        $this->call('GET', self::BASE.'/list', ['folder' => '/Docs'], $this->owner)->assertOk()->assertJsonPath('data.data.0.id', $id);
        $this->call('GET', self::BASE.'/trash/list', [], $this->owner)->assertOk()->assertJsonCount(0, 'data.data');
        $this->assertTrue(AuditLog::query()->where('auditable_type', 'media')->where('auditable_id', $id)->where('event', AuditEvent::RESTORED->value)->exists());
    }

    public function test_restoring_a_file_of_a_deleted_folder_recreates_the_folders(): void
    {
        $this->folder('Work');
        $this->folder('2026', '/Work');
        $id = $this->file('/Work/2026', 'plan.txt');
        $workId = (int) MediaMgmt::query()->where('virtual_path', '/Work')->value('id');
        $this->deleteItems([$workId]);

        // Only files are listed; the folders come back with them
        $this->call('GET', self::BASE.'/trash/list', [], $this->owner)->assertOk()->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.id', $id);
        $this->call('POST', self::BASE."/trash/restore/{$id}", [], $this->owner)->assertOk()->assertJsonPath('data.data.path', '/Work/2026/plan.txt');

        $this->call('GET', self::BASE.'/list', ['folder' => '/Work'], $this->owner)->assertOk()->assertJsonPath('data.data.0.name', '2026');
        $this->call('GET', self::BASE.'/list', ['folder' => '/Work/2026'], $this->owner)->assertOk()->assertJsonPath('data.data.0.name', 'plan.txt');
    }

    public function test_a_taken_name_gets_a_restored_suffix(): void
    {
        $first = $this->file(MediaConst::ROOT, 'a.txt');
        $this->deleteItems([$first]);
        $second = $this->file(MediaConst::ROOT, 'a.txt');
        $this->deleteItems([$second]);
        $this->file(MediaConst::ROOT, 'a.txt');

        $this->call('POST', self::BASE."/trash/restore/{$first}", [], $this->owner)->assertOk()->assertJsonPath('data.data.name', 'a (restored).txt');
        $this->call('POST', self::BASE."/trash/restore/{$second}", [], $this->owner)->assertOk()->assertJsonPath('data.data.name', 'a (restored 2).txt');
    }

    public function test_after_thirty_days_or_once_minio_dropped_it_a_file_cannot_be_restored(): void
    {
        $old = $this->file(MediaConst::ROOT, 'old.txt');
        $this->deleteItems([$old]);
        $this->travel(MediaConst::TRASH_DAYS + 1)->days();

        $this->call('GET', self::BASE.'/trash/list', [], $this->owner)->assertOk()->assertJsonCount(0, 'data.data');
        $this->call('POST', self::BASE."/trash/restore/{$old}", [], $this->owner)->assertStatus(CommonVal::HTTP_NOT_FOUND);

        $gone = $this->file(MediaConst::ROOT, 'gone.txt');
        $this->deleteItems([$gone]);
        $this->storage->kept = [];
        $this->call('POST', self::BASE."/trash/restore/{$gone}", [], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonPath('error.messages.id.0', Messages::E0040);
        $this->assertTrue(MediaMgmt::query()->findOrFail($gone)->is_delete);
    }

    public function test_files_are_deleted_for_good_one_by_one_or_all_at_once(): void
    {
        $a = $this->file(MediaConst::ROOT, 'a.txt');
        $b = $this->file(MediaConst::ROOT, 'b.txt');
        $c = $this->file(MediaConst::ROOT, 'c.txt');
        $live = $this->file(MediaConst::ROOT, 'live.txt');
        $keys = [$a => $this->keyOf($a), $b => $this->keyOf($b), $c => $this->keyOf($c)];
        $this->deleteItems([$a, $b, $c]);

        $this->call('POST', self::BASE.'/trash/delete', ['ids' => [$a, $live]], $this->owner)->assertOk()->assertJsonPath('data', 1);
        $this->assertSame([$keys[$a]], $this->storage->purged);
        $this->assertNull(MediaMgmt::query()->find($a));
        $this->assertNotNull(MediaMgmt::query()->find($live)); // not in the trash: untouched

        $this->call('POST', self::BASE.'/trash/empty', [], $this->owner)->assertOk()->assertJsonPath('data', 2);
        $this->assertEqualsCanonicalizing(array_values($keys), $this->storage->purged);
        $this->assertSame(3, AuditLog::query()->where('auditable_type', 'media')->where('event', AuditEvent::PURGED->value)->count());
        $this->call('GET', self::BASE.'/trash/list', [], $this->owner)->assertOk()->assertJsonCount(0, 'data.data');
    }

    public function test_the_viewer_sees_the_trash_but_cannot_change_it(): void
    {
        $id = $this->file(MediaConst::ROOT, 'a.txt');
        $this->deleteItems([$id]);
        $viewer = $this->loginAs(AdminMst::factory()->create());

        $this->call('GET', self::BASE.'/trash/list', [], $viewer)->assertOk()->assertJsonCount(1, 'data.data');
        $this->call('POST', self::BASE."/trash/restore/{$id}", [], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
        $this->call('POST', self::BASE.'/trash/delete', ['ids' => [$id]], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
        $this->call('POST', self::BASE.'/trash/empty', [], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    private function folder(string $name, string $parent = MediaConst::ROOT): void
    {
        $this->call('POST', self::BASE.'/store-folder', ['folder' => $parent, 'name' => $name], $this->owner)->assertOk();
    }

    private function file(string $folder, string $name): int
    {
        $id = $this->call('POST', self::BASE.'/upload-url', ['folder' => $folder, 'name' => $name, 'size' => 10, 'mime_type' => 'text/plain'], $this->owner)
            ->assertOk()->json('data.id');
        $this->storage->put($this->keyOf($id), 10);
        $this->call('POST', self::BASE."/complete/{$id}", [], $this->owner)->assertOk();

        return $id;
    }

    /** @param  list<int>  $ids */
    private function deleteItems(array $ids): void
    {
        $this->call('POST', self::BASE.'/delete', ['ids' => $ids], $this->owner)->assertOk();
    }

    private function keyOf(int $id): string
    {
        return (string) MediaMgmt::query()->findOrFail($id)->minio_object_key;
    }
}
