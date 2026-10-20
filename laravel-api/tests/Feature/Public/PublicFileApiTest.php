<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Constants\MediaConst;
use App\Models\Content\CategoryAttachment;
use App\Models\Content\ContentCategory;
use App\Models\Master\AdminMst;
use App\Models\Media\MediaMgmt;
use Illuminate\Support\Str;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\UploadsFiles;
use Tests\TestCase;

/**
 * Files attached to public categories (ADR-0022): listed without folder paths, opened by key.
 */
final class PublicFileApiTest extends TestCase
{
    use AuthenticatesAdmins;
    use UploadsFiles;

    /** @var array<string, string> */
    private array $owner;

    private ContentCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeStorage();
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
        $this->category = ContentCategory::factory()->create(['slug' => 'aws']);
    }

    public function test_the_category_lists_available_files_without_folder_paths(): void
    {
        $pdf = $this->uploadFile($this->owner, 'certificate.pdf', 2048);
        $trashed = $this->uploadFile($this->owner, 'old.pdf');
        $key = $this->attach($this->category, $pdf);
        $this->attach($this->category, $trashed);
        $this->call('POST', '/api/admin/media/delete', ['ids' => [$trashed]], $this->owner)->assertOk();

        $this->getJson('/api/public/categories/aws')->assertOk()
            ->assertJsonPath('data.data.attachments.0.file', [
                'key' => $key,
                'name' => 'certificate.pdf',
                'size' => 2048,
                'mime_type' => 'application/octet-stream',
                'thumbnail_url' => null,
            ])
            ->assertJsonPath('data.data.attachments.1.file', null);
    }

    public function test_an_image_shows_its_thumbnail(): void
    {
        $photo = $this->uploadFile($this->owner, 'photo.jpg');
        MediaMgmt::query()->findOrFail($photo)->update(['metadata' => [MediaConst::META_THUMBNAIL => MediaConst::THUMBNAIL_PREFIX.'photo.jpg.jpg']]);
        $this->attach($this->category, $photo);

        $url = (string) $this->getJson('/api/public/categories/aws')->assertOk()->json('data.data.attachments.0.file.thumbnail_url');
        $this->assertStringContainsString(MediaConst::THUMBNAIL_PREFIX, $url);
        $this->assertStringContainsString('inline=image%2Fjpeg', $url);
    }

    public function test_a_file_of_a_public_category_gets_a_short_lived_link(): void
    {
        $pdf = $this->attach($this->category, $this->uploadFile($this->owner, 'certificate.pdf'));
        $zip = $this->attach($this->category, $this->uploadFile($this->owner, 'source.zip'));

        // Shown in the browser with the type the server picked; anything else downloads
        $this->getJson("/api/public/files/{$pdf}")->assertOk()
            ->assertJsonPath('data.url', fn (string $url): bool => str_contains($url, 'inline=application%2Fpdf')
                && str_contains($url, 'X-Amz-Expires='.MediaConst::PREVIEW_URL_TTL_SECONDS));
        $this->getJson("/api/public/files/{$zip}")->assertOk()
            ->assertJsonPath('data.url', fn (string $url): bool => ! str_contains($url, 'inline=')
                && str_contains($url, 'X-Amz-Expires='.MediaConst::DOWNLOAD_URL_TTL_SECONDS));
    }

    public function test_files_not_on_the_public_site_are_not_found(): void
    {
        $private = $this->attach(ContentCategory::factory()->private()->create(), $this->uploadFile($this->owner, 'draft.txt'));
        $inPrivateGroup = $this->attach(
            ContentCategory::factory()->in(ContentCategory::factory()->private()->create())->create(),
            $this->uploadFile($this->owner, 'hidden.txt'),
        );
        $trashedId = $this->uploadFile($this->owner, 'trashed.txt');
        $trashed = $this->attach($this->category, $trashedId);
        $this->call('POST', '/api/admin/media/delete', ['ids' => [$trashedId]], $this->owner)->assertOk();

        // Removed from the category: the old link stops working even though the file is still in Files
        $detached = $this->attach($this->category, $this->uploadFile($this->owner, 'detached.txt'));
        CategoryAttachment::query()->where('public_id', $detached)->delete();

        foreach ([$private, $inPrivateGroup, $trashed, $detached, (string) Str::uuid()] as $key) {
            $this->getJson("/api/public/files/{$key}")->assertNotFound();
        }
        // Not a key at all: no route
        $this->getJson('/api/public/files/1')->assertNotFound();
    }

    /**
     * @return string the attachment's public key
     */
    private function attach(ContentCategory $category, int $mediaId): string
    {
        $attachment = CategoryAttachment::query()->create(['content_category_id' => $category->id, 'title' => 'File', 'media_id' => $mediaId, 'position' => $mediaId]);

        return (string) $attachment->refresh()->public_id;
    }
}
