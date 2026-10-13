<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Constants\CommonVal;
use App\Constants\MediaConst;
use App\Enums\UploadStatus;
use App\Models\Audit\AuditLog;
use App\Models\Master\AdminMst;
use App\Models\Media\MediaMgmt;
use App\Services\Media\ObjectStorage;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Fakes\FakeObjectStorage;
use Tests\TestCase;

/**
 * File manager (ADR-0014): folders, small-file upload through a presigned URL, download, rename, move, delete.
 */
final class MediaApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const BASE = '/api/admin/media';

    /** @var array<string, string> */
    private array $owner;

    private FakeObjectStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = new FakeObjectStorage;
        $this->app->instance(ObjectStorage::class, $this->storage);
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
    }

    public function test_upload_complete_list_and_download(): void
    {
        $folderId = $this->call('POST', self::BASE.'/store-folder', ['name' => 'Docs'], $this->owner)->assertOk()->json('data');

        $upload = $this->call('POST', self::BASE.'/upload-url', [
            'folder' => '/Docs', 'name' => 'Báo cáo.pdf', 'size' => 1234, 'mime_type' => 'application/pdf',
        ], $this->owner)->assertOk()->json('data');
        $this->assertSame('PUT', $upload['method']);
        $this->assertSame(['Content-Type' => 'application/pdf'], $upload['headers']);

        // Not in the list while uploading, and not finished before the object exists
        $this->call('GET', self::BASE.'/list', ['folder' => '/Docs'], $this->owner)->assertOk()->assertJsonCount(0, 'data.data');
        $this->call('POST', self::BASE."/complete/{$upload['id']}", [], $this->owner)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);

        $this->storage->put($this->keyOf($upload['id']), 1234, 'application/pdf');
        $this->call('POST', self::BASE."/complete/{$upload['id']}", [], $this->owner)
            ->assertOk()
            ->assertJsonPath('data.data.path', '/Docs/Báo cáo.pdf')
            ->assertJsonPath('data.data.upload_status', UploadStatus::COMPLETED->value);
        $this->call('POST', self::BASE."/complete/{$upload['id']}", [], $this->owner)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);

        $this->call('GET', self::BASE.'/list', [], $this->owner)
            ->assertOk()->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.id', $folderId);
        $this->call('GET', self::BASE.'/list', ['folder' => '/Docs'], $this->owner)
            ->assertOk()->assertJsonPath('data.data.0.name', 'Báo cáo.pdf')->assertJsonPath('data.data.0.size', 1234);

        $download = $this->call('GET', self::BASE."/download-url/{$upload['id']}", [], $this->owner)->assertOk()->json('data');
        $this->assertStringContainsString('X-Amz-Expires='.MediaConst::DOWNLOAD_URL_TTL_SECONDS, $download['url']);

        $this->assertSame(2, AuditLog::query()->where('auditable_type', 'media')->count());
    }

    public function test_a_wrong_size_is_refused_and_the_object_removed(): void
    {
        $id = $this->startUpload('photo.jpg', 100);
        $this->storage->put($this->keyOf($id), 999);

        $this->call('POST', self::BASE."/complete/{$id}", [], $this->owner)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->assertSame([$this->keyOf($id)], $this->storage->deleted);
    }

    public function test_validation(): void
    {
        $tooBig = ['name' => 'big.zip', 'size' => MediaConst::SINGLE_UPLOAD_MAX_BYTES + 1, 'mime_type' => 'application/zip'];
        $this->call('POST', self::BASE.'/upload-url', $tooBig, $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonStructure(['error' => ['messages' => ['size']]]);

        $blocked = ['name' => 'run.EXE', 'size' => 10, 'mime_type' => 'application/octet-stream'];
        $this->call('POST', self::BASE.'/upload-url', $blocked, $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonStructure(['error' => ['messages' => ['name']]]);

        foreach (['a/b', '..', ''] as $name) {
            $this->call('POST', self::BASE.'/store-folder', ['name' => $name], $this->owner)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        }
        $this->call('POST', self::BASE.'/store-folder', ['folder' => '/Missing', 'name' => 'x'], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonStructure(['error' => ['messages' => ['folder']]]);
        $this->call('GET', self::BASE.'/list', ['folder' => 'no-slash'], $this->owner)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    }

    public function test_names_are_unique_per_folder_ignoring_case_until_deleted(): void
    {
        $id = $this->call('POST', self::BASE.'/store-folder', ['name' => 'Photos'], $this->owner)->assertOk()->json('data');
        $this->call('POST', self::BASE.'/store-folder', ['name' => 'PHOTOS'], $this->owner)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);

        $this->call('POST', self::BASE.'/delete', ['ids' => [$id]], $this->owner)->assertOk();
        $this->call('POST', self::BASE.'/store-folder', ['name' => 'Photos'], $this->owner)->assertOk();
    }

    public function test_rename_and_move_a_folder_with_its_content(): void
    {
        $this->call('POST', self::BASE.'/store-folder', ['name' => 'A'], $this->owner)->assertOk();
        $b = $this->call('POST', self::BASE.'/store-folder', ['name' => 'B'], $this->owner)->assertOk()->json('data');
        $this->call('POST', self::BASE.'/store-folder', ['folder' => '/B', 'name' => 'C_%'], $this->owner)->assertOk();
        $file = $this->finishedUpload('/B/C_%', 'note.txt', 5);

        $this->call('PUT', self::BASE."/update/{$b}", ['folder' => '/B/C_%'], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonStructure(['error' => ['messages' => ['folder']]]);

        $this->call('PUT', self::BASE."/update/{$b}", ['folder' => '/A', 'name' => 'Bee'], $this->owner)->assertOk();
        $this->assertSame('/A/Bee/C_%/note.txt', MediaMgmt::query()->findOrFail($file)->virtual_path);

        $this->call('PUT', self::BASE."/update/{$file}", ['name' => 'renamed.txt'], $this->owner)->assertOk();
        $this->call('GET', self::BASE.'/list', ['folder' => '/A/Bee/C_%'], $this->owner)
            ->assertOk()->assertJsonPath('data.data.0.name', 'renamed.txt');
    }

    public function test_deleting_a_folder_deletes_everything_inside_and_the_objects(): void
    {
        $folder = $this->call('POST', self::BASE.'/store-folder', ['name' => 'Trash'], $this->owner)->assertOk()->json('data');
        $file = $this->finishedUpload('/Trash', 'old.txt', 3);
        $key = $this->keyOf($file);

        $this->call('POST', self::BASE.'/delete', ['ids' => [$folder]], $this->owner)->assertOk();

        $this->assertTrue(MediaMgmt::query()->findOrFail($file)->is_delete);
        $this->assertSame([$key], $this->storage->deleted);
        $this->call('GET', self::BASE."/download-url/{$file}", [], $this->owner)->assertStatus(CommonVal::HTTP_NOT_FOUND);
    }

    public function test_viewer_browses_and_downloads_but_cannot_write(): void
    {
        $file = $this->finishedUpload(MediaConst::ROOT, 'shared.txt', 1);
        $viewer = $this->loginAs(AdminMst::factory()->create());

        $this->call('GET', self::BASE.'/list', [], $viewer)->assertOk();
        $this->call('GET', self::BASE."/download-url/{$file}", [], $viewer)->assertOk();
        $this->call('POST', self::BASE.'/store-folder', ['name' => 'Nope'], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
        $this->call('POST', self::BASE.'/upload-url', ['name' => 'a.txt', 'size' => 1, 'mime_type' => 'text/plain'], $viewer)
            ->assertStatus(CommonVal::HTTP_FORBIDDEN);
        $this->call('POST', self::BASE.'/delete', ['ids' => [$file]], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    public function test_guest_is_refused(): void
    {
        $this->flushSession();

        $this->call('GET', self::BASE.'/list')->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    private function startUpload(string $name, int $size, string $folder = MediaConst::ROOT): int
    {
        return $this->call('POST', self::BASE.'/upload-url', ['folder' => $folder, 'name' => $name, 'size' => $size, 'mime_type' => 'text/plain'], $this->owner)
            ->assertOk()->json('data.id');
    }

    private function finishedUpload(string $folder, string $name, int $size): int
    {
        $id = $this->startUpload($name, $size, $folder);
        $this->storage->put($this->keyOf($id), $size);
        $this->call('POST', self::BASE."/complete/{$id}", [], $this->owner)->assertOk();

        return $id;
    }

    private function keyOf(int $id): string
    {
        return (string) MediaMgmt::query()->findOrFail($id)->minio_object_key;
    }
}
