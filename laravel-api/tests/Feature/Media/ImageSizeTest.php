<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Enums\UploadStatus;
use App\Models\Master\AdminMst;
use App\Models\Media\MediaMgmt;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\UploadsFiles;
use Tests\TestCase;

/**
 * MEDIA-10: the width and height of an uploaded image, read from the start of its object.
 */
final class ImageSizeTest extends TestCase
{
    use AuthenticatesAdmins;
    use UploadsFiles;

    /** @var array<string, string> */
    private array $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeStorage();
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
    }

    public function test_an_uploaded_image_gets_its_width_and_height(): void
    {
        $this->upload('photo.PNG', $this->png(640, 480));
        $this->upload('wave.gif', 'GIF89a'.pack('vv', 32, 16).str_repeat("\0", 10));
        $document = $this->upload('notes.pdf', $this->png(10, 10));
        $broken = $this->upload('broken.jpg', 'not an image');

        $rows = collect($this->call('GET', '/api/admin/media/list', ['folder' => '/'], $this->owner)->assertOk()->json('data.data'))->keyBy('name');

        $this->assertSame([640, 480], [$rows['photo.PNG']['width'], $rows['photo.PNG']['height']]);
        $this->assertSame([32, 16], [$rows['wave.gif']['width'], $rows['wave.gif']['height']]);
        // Only image types are read; an unreadable image still uploads, without a size
        $this->assertNull($rows['notes.pdf']['width']);
        $this->assertNull($rows['broken.jpg']['width']);
        $this->assertSame(2, MediaMgmt::query()->whereKey([$document, $broken])->where('upload_status', UploadStatus::COMPLETED)->count());
    }

    public function test_the_command_reads_images_uploaded_before_without_touching_them(): void
    {
        $old = $this->upload('old.png', $this->png(1920, 1080));
        $deleted = $this->upload('gone.png', $this->png(5, 5));
        // As if uploaded before sizes were read
        MediaMgmt::query()->whereKey([$old, $deleted])->toBase()->update(['width' => null, 'height' => null, 'updated_at' => '2026-01-01 00:00:00']);
        $this->call('POST', '/api/admin/media/delete', ['ids' => [$deleted]], $this->owner)->assertOk();

        $this->artisan('media:read-image-sizes')->expectsOutputToContain('Read 1 image size(s)')->assertSuccessful();

        $file = MediaMgmt::query()->findOrFail($old);
        $this->assertSame([1920, 1080], [$file->width, $file->height]);
        $this->assertSame('2026-01-01', $file->updated_at?->toDateString());
        $this->assertNull(MediaMgmt::query()->findOrFail($deleted)->width);
        // Nothing left to read
        $this->artisan('media:read-image-sizes')->expectsOutputToContain('Read 0 image size(s)')->assertSuccessful();
    }

    private function upload(string $name, string $body): int
    {
        $id = $this->call('POST', '/api/admin/media/upload-url', ['folder' => '/', 'name' => $name, 'size' => strlen($body), 'mime_type' => 'application/octet-stream'], $this->owner)
            ->assertOk()->json('data.id');
        $this->storage->putBody((string) MediaMgmt::query()->findOrFail($id)->minio_object_key, $body);
        $this->call('POST', "/api/admin/media/complete/{$id}", [], $this->owner)->assertOk();

        return $id;
    }

    /** The PNG signature and header chunk: all getimagesize needs. */
    private function png(int $width, int $height): string
    {
        return "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.pack('NN', $width, $height)."\x08\x02\x00\x00\x00".pack('N', 0);
    }
}
