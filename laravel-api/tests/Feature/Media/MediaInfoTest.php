<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Constants\MediaConst;
use App\Models\Master\AdminMst;
use App\Models\Media\MediaMgmt;
use Illuminate\Process\Factory;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\UploadsFiles;
use Tests\TestCase;

/**
 * MEDIA-11: duration of videos and audio, thumbnails of images and videos, read by ffprobe / ffmpeg
 * (faked here) after the upload completes.
 */
final class MediaInfoTest extends TestCase
{
    use AuthenticatesAdmins;
    use UploadsFiles;

    private const JPEG = "\xFF\xD8\xFF\xE0 thumbnail";

    /** @var array<string, string> */
    private array $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeStorage();
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
    }

    public function test_a_video_gets_its_duration_size_and_a_frame_as_thumbnail(): void
    {
        $this->fakeFfmpeg(['format' => ['duration' => '74.6'], 'streams' => [['codec_type' => 'audio'], ['width' => 1280, 'height' => 720]]]);

        $id = $this->upload('clip.mp4', str_repeat('v', 100));

        $row = $this->row('clip.mp4');
        $this->assertSame(75, $row['duration']);
        $this->assertSame([1280, 720], [$row['width'], $row['height']]);
        $this->assertStringContainsString('thumbnails/', (string) $row['thumbnail_url']);
        $this->assertStringContainsString('inline=image%2Fjpeg', (string) $row['thumbnail_url']);
        $key = MediaConst::THUMBNAIL_PREFIX.MediaMgmt::query()->findOrFail($id)->minio_object_key.'.jpg';
        // The fake process adds a final newline to its output
        $this->assertStringStartsWith(self::JPEG, $this->storage->bodies[$key]);
        $this->assertSame(MediaConst::THUMBNAIL_CONTENT_TYPE, $this->storage->objects[$key]['mime_type']);
        // Limited in time and to known containers, the frame one second in
        Process::assertRan(fn (PendingProcess $process): bool => $this->isFfmpeg($process) && in_array('-ss', (array) $process->command, true)
            && ((array) $process->command)[array_search('-ss', (array) $process->command, true) + 1] === '1'
            && $process->timeout === MediaConst::FFMPEG_TIMEOUT_SECONDS
            && in_array(MediaConst::FFMPEG_FORMATS, (array) $process->command, true));
    }

    public function test_audio_gets_a_duration_and_an_image_a_thumbnail(): void
    {
        $this->fakeFfmpeg(['format' => ['duration' => '183.2'], 'streams' => [['codec_type' => 'audio']]]);

        $this->upload('song.mp3', str_repeat('a', 100));
        $this->assertSame(183, $this->row('song.mp3')['duration']);
        $this->assertNull($this->row('song.mp3')['thumbnail_url']);
        Process::assertDidntRun(fn (PendingProcess $process): bool => $this->isFfmpeg($process));

        $this->upload('photo.png', $this->png(640, 480));
        $this->assertNotNull($this->row('photo.png')['thumbnail_url']);
        $this->assertNull($this->row('photo.png')['duration']);
        // Nothing to probe in an image; it keeps the size from its header
        Process::assertRanTimes(fn (PendingProcess $process): bool => ((array) $process->command)[0] === 'ffprobe', 1);
        $this->assertSame(640, $this->row('photo.png')['width']);

        // A huge image is not decoded; a document is not read at all
        $this->upload('poster.png', $this->png(10000, 6000));
        $this->upload('notes.pdf', str_repeat('p', 100));
        $this->assertNull($this->row('poster.png')['thumbnail_url']);
        Process::assertRanTimes(fn (PendingProcess $process): bool => $this->isFfmpeg($process), 1);
    }

    public function test_a_file_ffmpeg_cannot_read_still_uploads_and_is_not_read_again(): void
    {
        $this->fakeProcesses(['*' => Process::result(errorOutput: 'Invalid data found', exitCode: 1)]);

        $id = $this->upload('broken.mp4', str_repeat('x', 100));

        $row = $this->row('broken.mp4');
        $this->assertNull($row['duration']);
        $this->assertNull($row['thumbnail_url']);
        $this->assertTrue(MediaMgmt::query()->findOrFail($id)->metadata[MediaConst::META_INFO_READ] ?? false);
        $this->artisan('media:read-media-info')->expectsOutputToContain('Read 0 file(s)')->assertSuccessful();
    }

    public function test_the_command_reads_older_files_and_purging_removes_the_thumbnail(): void
    {
        $this->fakeFfmpeg(['format' => ['duration' => '10'], 'streams' => [['width' => 64, 'height' => 48]]]);
        $id = $this->upload('old.webm', str_repeat('w', 100));
        $updatedAt = '2026-01-01 00:00:00';
        // As if uploaded before MEDIA-11
        MediaMgmt::query()->whereKey($id)->toBase()->update(['metadata' => null, 'duration' => null, 'updated_at' => $updatedAt]);

        $this->artisan('media:read-media-info')->expectsOutputToContain('Read 1 file(s)')->assertSuccessful();
        $file = MediaMgmt::query()->findOrFail($id);
        $this->assertSame(10, $file->duration);
        $this->assertSame('2026-01-01', $file->updated_at?->toDateString());
        $this->artisan('media:read-media-info')->expectsOutputToContain('Read 0 file(s)')->assertSuccessful();

        // Deleted: the thumbnail stays for the trash; deleted forever: it goes with the file
        $thumbnail = (string) $file->metadata[MediaConst::META_THUMBNAIL];
        $this->call('POST', '/api/admin/media/delete', ['ids' => [$id]], $this->owner)->assertOk();
        $this->assertArrayHasKey($thumbnail, $this->storage->objects);
        $this->call('POST', '/api/admin/media/trash/delete', ['ids' => [$id]], $this->owner)->assertOk();
        $this->assertContains($thumbnail, $this->storage->purged);
    }

    /** @param  array<string, mixed>  $probe  what ffprobe prints */
    private function fakeFfmpeg(array $probe): void
    {
        $this->fakeProcesses([
            '*ffprobe*' => Process::result(output: (string) json_encode($probe)),
            '*ffmpeg*' => Process::result(output: self::JPEG),
        ]);
    }

    /**
     * TestCase already fakes every process with an empty output, and that catch-all would match first.
     *
     * @param  array<string, mixed>  $results
     */
    private function fakeProcesses(array $results): void
    {
        Process::swap(new Factory);
        Process::fake($results);
    }

    private function isFfmpeg(PendingProcess $process): bool
    {
        return ((array) $process->command)[0] === 'ffmpeg';
    }

    /** @return array<string, mixed> */
    private function row(string $name): array
    {
        return collect($this->call('GET', '/api/admin/media/list', ['folder' => '/'], $this->owner)->assertOk()->json('data.data'))
            ->keyBy('name')[$name];
    }

    private function upload(string $name, string $body): int
    {
        $id = $this->call('POST', '/api/admin/media/upload-url', ['folder' => '/', 'name' => $name, 'size' => strlen($body), 'mime_type' => 'application/octet-stream'], $this->owner)
            ->assertOk()->json('data.id');
        $this->storage->putBody((string) MediaMgmt::query()->findOrFail($id)->minio_object_key, $body);
        $this->call('POST', "/api/admin/media/complete/{$id}", [], $this->owner)->assertOk();

        return $id;
    }

    private function png(int $width, int $height): string
    {
        return "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.pack('NN', $width, $height)."\x08\x02\x00\x00\x00".pack('N', 0);
    }
}
