<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Constants\CommonVal;
use App\Constants\LedgerConst;
use App\Constants\Messages;
use App\Enums\EvidenceType;
use App\Models\Audit\AuditLog;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\Skill;
use App\Models\Master\AdminMst;
use App\Models\Media\MediaMgmt;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\UploadsFiles;
use Tests\TestCase;

/**
 * LEDGER-01: files of the file manager attached to evidence.
 */
final class EvidenceFilesApiTest extends TestCase
{
    use AuthenticatesAdmins;
    use UploadsFiles;

    private const BASE = '/api/admin/evidence';

    /** @var array<string, string> */
    private array $owner;

    private Skill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeStorage();
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
        $this->skill = Skill::factory()->create();
    }

    public function test_files_are_attached_in_order_listed_and_audited(): void
    {
        $pdf = $this->uploadFile($this->owner, 'certificate.pdf');
        $zip = $this->uploadFile($this->owner, 'source.zip');

        $id = $this->store([$zip, $pdf]);

        $this->call('GET', self::BASE.'/list', ['id' => $id], $this->owner)->assertOk()
            ->assertJsonPath('data.data.0.files.0.id', $zip)
            ->assertJsonPath('data.data.0.files.0.name', 'source.zip')
            ->assertJsonPath('data.data.0.files.0.preview', null)
            ->assertJsonPath('data.data.0.files.0.available', true)
            ->assertJsonPath('data.data.0.files.1.id', $pdf)
            ->assertJsonPath('data.data.0.files.1.preview', 'pdf');
        $audit = AuditLog::query()->where(['auditable_type' => 'evidence', 'auditable_id' => $id, 'event' => 'created'])->firstOrFail();
        $this->assertCount(2, $audit->new_values['files']);

        // Reorder and drop one; the file itself stays in the file manager
        $this->call('PUT', self::BASE."/update/{$id}", ['media_ids' => [$pdf]], $this->owner)->assertOk();
        $this->assertSame([$pdf], Evidence::query()->findOrFail($id)->files()->pluck('media_mgmt.id')->all());
        $this->assertDatabaseHas('media_mgmt', ['id' => $zip, 'is_delete' => false]);

        // An update without media_ids leaves the attachments alone; saving the same list keeps the public link
        $key = Evidence::query()->findOrFail($id)->files()->first()?->pivot->public_id;
        $this->call('PUT', self::BASE."/update/{$id}", ['title' => 'Renamed'], $this->owner)->assertOk();
        $this->call('PUT', self::BASE."/update/{$id}", ['media_ids' => [$pdf]], $this->owner)->assertOk();
        $this->assertSame([$pdf], Evidence::query()->findOrFail($id)->files()->pluck('media_mgmt.id')->all());
        $this->assertSame($key, Evidence::query()->findOrFail($id)->files()->first()?->pivot->public_id);
    }

    public function test_only_uploaded_live_files_can_be_attached(): void
    {
        $this->call('POST', '/api/admin/media/store-folder', ['folder' => '/', 'name' => 'Docs'], $this->owner)->assertOk();
        $folder = (int) MediaMgmt::query()->where('virtual_path', '/Docs')->value('id');
        $uploading = $this->call('POST', '/api/admin/media/upload-url', ['folder' => '/', 'name' => 'half.bin', 'size' => 10, 'mime_type' => 'application/octet-stream'], $this->owner)
            ->json('data.id');
        $deleted = $this->uploadFile($this->owner, 'old.txt');
        $this->call('POST', '/api/admin/media/delete', ['ids' => [$deleted]], $this->owner)->assertOk();

        foreach ([999999, $folder, $uploading, $deleted] as $mediaId) {
            $this->call('POST', self::BASE.'/store', $this->payload([$mediaId]), $this->owner)
                ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
                ->assertJsonPath('error.messages.media_ids.0', Messages::E0041);
        }

        $tooMany = range(1, LedgerConst::EVIDENCE_FILES_MAX + 1);
        $this->call('POST', self::BASE.'/store', $this->payload($tooMany), $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['media_ids']]]);
        $this->assertSame(0, Evidence::query()->count());
    }

    public function test_a_deleted_file_stays_attached_until_it_is_deleted_for_good(): void
    {
        $file = $this->uploadFile($this->owner, 'notes.txt');
        $id = $this->store([$file]);

        $this->call('POST', '/api/admin/media/delete', ['ids' => [$file]], $this->owner)->assertOk();
        $this->call('GET', self::BASE.'/list', ['id' => $id], $this->owner)->assertOk()
            ->assertJsonPath('data.data.0.files.0.available', false)
            ->assertJsonPath('data.data.0.files.0.preview', null);

        // The form posts its whole list back: the trashed file is still accepted
        $other = $this->uploadFile($this->owner, 'more.txt');
        $this->call('PUT', self::BASE."/update/{$id}", ['media_ids' => [$file, $other]], $this->owner)->assertOk();

        $this->call('POST', "/api/admin/media/trash/restore/{$file}", [], $this->owner)->assertOk();
        $this->call('GET', self::BASE.'/list', ['id' => $id], $this->owner)->assertOk()
            ->assertJsonPath('data.data.0.files.0.available', true)
            ->assertJsonPath('data.data.0.files.0.preview', 'text');

        $this->call('POST', '/api/admin/media/delete', ['ids' => [$file]], $this->owner)->assertOk();
        $this->call('POST', '/api/admin/media/trash/delete', ['ids' => [$file]], $this->owner)->assertOk();
        $this->call('GET', self::BASE.'/list', ['id' => $id], $this->owner)->assertOk()
            ->assertJsonCount(1, 'data.data.0.files')
            ->assertJsonPath('data.data.0.files.0.id', $other);
    }

    public function test_deleting_evidence_keeps_its_files(): void
    {
        $file = $this->uploadFile($this->owner, 'keep.txt');
        $id = $this->store([$file]);

        $this->call('POST', self::BASE.'/delete', ['ids' => [$id]], $this->owner)->assertOk();

        $this->assertDatabaseMissing('evidence_media', ['evidence_id' => $id]);
        $this->assertDatabaseHas('media_mgmt', ['id' => $file, 'is_delete' => false]);
    }

    public function test_imported_evidence_can_change_its_files(): void
    {
        $evidence = Evidence::factory()->fromObsidian()->create();
        $evidence->skills()->attach($this->skill->id);
        $file = $this->uploadFile($this->owner, 'slides.pdf');

        $this->call('PUT', self::BASE."/update/{$evidence->id}", ['media_ids' => [$file]], $this->owner)->assertOk();

        $this->assertSame([$file], $evidence->files()->pluck('media_mgmt.id')->all());
    }

    public function test_the_files_screen_shows_where_files_are_attached(): void
    {
        foreach (['a_b', 'aXb'] as $name) {
            $this->call('POST', '/api/admin/media/store-folder', ['folder' => '/', 'name' => $name], $this->owner)->assertOk();
        }
        $inside = $this->call('POST', '/api/admin/media/upload-url', ['folder' => '/aXb', 'name' => 'in.txt', 'size' => 10, 'mime_type' => 'text/plain'], $this->owner)->json('data.id');
        $this->storage->put((string) MediaMgmt::query()->findOrFail($inside)->minio_object_key, 10);
        $this->call('POST', "/api/admin/media/complete/{$inside}", [], $this->owner)->assertOk();
        $file = $this->uploadFile($this->owner, 'cert.pdf');
        $loose = $this->uploadFile($this->owner, 'loose.txt');
        $first = $this->store([$file, $inside]);
        $second = $this->store([$file]);

        $rows = collect($this->call('GET', '/api/admin/media/list', ['folder' => '/'], $this->owner)->assertOk()->json('data.data'))->keyBy('name');

        $this->assertSame(2, $rows['cert.pdf']['attached_count']);
        $this->assertEqualsCanonicalizing([$first, $second], array_column($rows['cert.pdf']['used_by'], 'id'));
        $this->assertSame(0, $rows['loose.txt']['attached_count']);
        $this->assertSame([], $rows['loose.txt']['used_by']);
        // A folder counts the files inside it; `_` in a name is not a wildcard
        $this->assertSame(1, $rows['aXb']['attached_count']);
        $this->assertSame(0, $rows['a_b']['attached_count']);

        // A deleted file inside no longer counts
        $this->call('POST', '/api/admin/media/delete', ['ids' => [$inside]], $this->owner)->assertOk();
        $this->assertSame(0, collect($this->call('GET', '/api/admin/media/list', ['folder' => '/'], $this->owner)->json('data.data'))->keyBy('name')['aXb']['attached_count']);
        $this->assertNotNull($loose);
    }

    /**
     * @param  list<int>  $mediaIds
     * @return array<string, mixed>
     */
    private function payload(array $mediaIds): array
    {
        return [
            'type' => EvidenceType::NOTE->value,
            'title' => 'Certificate',
            'url' => 'https://example.com/certificate',
            'occurred_on' => '2026-10-15',
            'skill_ids' => [$this->skill->id],
            'media_ids' => $mediaIds,
        ];
    }

    /**
     * @param  list<int>  $mediaIds
     */
    private function store(array $mediaIds): int
    {
        return (int) $this->call('POST', self::BASE.'/store', $this->payload($mediaIds), $this->owner)->assertOk()->json('data');
    }
}
