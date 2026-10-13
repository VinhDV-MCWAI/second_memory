<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Constants\CommonVal;
use App\Constants\MediaConst;
use App\Constants\Messages;
use App\Enums\BackgroundTaskStatus;
use App\Enums\UploadStatus;
use App\Events\BackgroundTaskFinished;
use App\Models\Master\AdminMst;
use App\Models\Media\MediaMgmt;
use App\Services\Media\MultipartUploadService;
use App\Services\Media\ObjectStorage;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Fakes\FakeObjectStorage;
use Tests\TestCase;

/**
 * Large files in parts (ADR-0014): start, part URLs, complete in the background, abort, clean-up.
 */
final class MultipartUploadApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const BASE = '/api/admin/media/multipart';

    private const MB = 1024 * 1024;

    /** 50 MB → four 16 MB parts, the last one 2 MB. */
    private const SIZE = 50 * self::MB;

    /** @var array<string, string> */
    private array $owner;

    private FakeObjectStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = new FakeObjectStorage;
        $this->app->instance(ObjectStorage::class, $this->storage);
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
        Event::fake([BackgroundTaskFinished::class]);
    }

    public function test_part_size_grows_with_the_file(): void
    {
        $this->assertSame(16 * self::MB, MultipartUploadService::partSize(150 * self::MB));
        $this->assertSame(32 * self::MB, MultipartUploadService::partSize(600 * self::MB));
        $this->assertSame(320, MultipartUploadService::partCount(MediaConst::MAX_FILE_BYTES));
        $this->assertSame(4, MultipartUploadService::partCount(self::SIZE));
    }

    public function test_a_large_file_goes_up_in_parts_and_is_processed_in_the_background(): void
    {
        $start = $this->call('POST', self::BASE.'/start', ['name' => 'video.mp4', 'size' => self::SIZE, 'mime_type' => 'video/mp4'], $this->owner)
            ->assertOk()->assertJsonPath('data.part_size', 16 * self::MB)->assertJsonPath('data.part_count', 4)->json('data');
        $id = $start['id'];

        $urls = $this->call('POST', self::BASE."/part-urls/{$id}", ['part_numbers' => [1, 2, 3, 4]], $this->owner)->assertOk()->json('data.parts');
        $this->assertCount(4, $urls);
        $this->assertStringContainsString('partNumber=3', $urls[2]['url']);
        $this->call('POST', self::BASE."/part-urls/{$id}", ['part_numbers' => [5]], $this->owner)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);

        $parts = $this->uploadParts($id);
        $this->call('POST', self::BASE."/complete/{$id}", ['parts' => array_slice($parts, 0, 3)], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonPath('error.messages.parts.0', Messages::E0037);

        $task = $this->call('POST', self::BASE."/complete/{$id}", ['parts' => $parts], $this->owner)
            ->assertOk()->assertJsonPath('data.data.type', MediaConst::TASK_PROCESS)->assertJsonPath('data.data.subject_id', $id)->json('data.data');

        // The sync queue ran the job inside the request
        $file = MediaMgmt::query()->findOrFail($id);
        $this->assertSame(UploadStatus::COMPLETED, $file->upload_status);
        $this->assertNull($file->upload_id);
        $this->call('GET', "/api/admin/background-task/status/{$task['id']}", [], $this->owner)
            ->assertOk()->assertJsonPath('data.data.status', BackgroundTaskStatus::COMPLETED->value)->assertJsonPath('data.data.message', 'video.mp4 is ready');
        Event::assertDispatched(BackgroundTaskFinished::class, fn (BackgroundTaskFinished $e): bool => $e->task['subject_id'] === $id);
        $this->call('GET', '/api/admin/media/download-url/'.$id, [], $this->owner)->assertOk();
    }

    public function test_parts_minio_refuses_fail_the_upload_without_retrying(): void
    {
        $id = $this->start('broken.bin');
        $parts = $this->uploadParts($id);
        $parts[1]['etag'] = 'tampered';

        $task = $this->call('POST', self::BASE."/complete/{$id}", ['parts' => $parts], $this->owner)->assertOk()->json('data.data');

        $this->assertTrue(MediaMgmt::query()->findOrFail($id)->is_delete);
        $this->assertSame(UploadStatus::FAILED, MediaMgmt::query()->findOrFail($id)->upload_status);
        $this->call('GET', "/api/admin/background-task/status/{$task['id']}", [], $this->owner)
            ->assertJsonPath('data.data.status', BackgroundTaskStatus::FAILED->value)->assertJsonPath('data.data.message', Messages::E0038);
        // The name is free again
        $this->start('broken.bin');
    }

    public function test_abort_and_ownership(): void
    {
        $id = $this->start('cancel.iso');

        $other = $this->loginAsOwner(AdminMst::factory()->create());
        $this->call('POST', self::BASE."/part-urls/{$id}", ['part_numbers' => [1]], $other)->assertStatus(CommonVal::HTTP_NOT_FOUND);
        $this->call('POST', self::BASE."/abort/{$id}", [], $other)->assertStatus(CommonVal::HTTP_NOT_FOUND);

        $this->call('POST', self::BASE."/abort/{$id}", [], $this->owner)->assertOk();
        $this->assertSame(['upload-1'], $this->storage->aborted);
        $this->call('POST', self::BASE."/abort/{$id}", [], $this->owner)->assertStatus(CommonVal::HTTP_NOT_FOUND);
    }

    public function test_validation_and_viewer(): void
    {
        $this->call('POST', self::BASE.'/start', ['name' => 'huge.bin', 'size' => MediaConst::MAX_FILE_BYTES + 1, 'mime_type' => 'application/octet-stream'], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)->assertJsonStructure(['error' => ['messages' => ['size']]]);

        $viewer = $this->loginAs(AdminMst::factory()->create());
        $this->call('POST', self::BASE.'/start', ['name' => 'a.bin', 'size' => self::SIZE, 'mime_type' => 'application/octet-stream'], $viewer)
            ->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    public function test_stuck_uploads_are_cleaned_after_a_day(): void
    {
        $stuck = $this->start('stuck.bin');
        $this->travel(MediaConst::STUCK_UPLOAD_HOURS + 1)->hours();
        $fresh = $this->start('fresh.bin');

        $this->artisan('media:clean-stuck-uploads')->expectsOutput('Cleaned 1 stuck upload(s).')->assertSuccessful();

        $this->assertTrue(MediaMgmt::query()->findOrFail($stuck)->is_delete);
        $this->assertFalse(MediaMgmt::query()->findOrFail($fresh)->is_delete);
        $this->assertSame(['upload-1'], $this->storage->aborted);
    }

    private function start(string $name): int
    {
        return $this->call('POST', self::BASE.'/start', ['name' => $name, 'size' => self::SIZE, 'mime_type' => 'application/octet-stream'], $this->owner)
            ->assertOk()->json('data.id');
    }

    /**
     * What the browser does: PUT every part, keep each ETag.
     *
     * @return list<array{part_number: int, etag: string}>
     */
    private function uploadParts(int $id): array
    {
        $uploadId = (string) MediaMgmt::query()->findOrFail($id)->upload_id;
        $partSize = MultipartUploadService::partSize(self::SIZE);
        $parts = [];
        for ($number = 1; $number <= 4; $number++) {
            $size = min($partSize, self::SIZE - ($number - 1) * $partSize);
            $parts[] = ['part_number' => $number, 'etag' => $this->storage->uploadPart($uploadId, $number, $size)];
        }

        return $parts;
    }
}
