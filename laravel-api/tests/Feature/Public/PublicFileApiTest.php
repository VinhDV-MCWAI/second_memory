<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Constants\MediaConst;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\Skill;
use App\Models\Master\AdminMst;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\Concerns\UploadsFiles;
use Tests\TestCase;

/**
 * LEDGER-01: files attached to public evidence on the public Skill Ledger.
 */
final class PublicFileApiTest extends TestCase
{
    use AuthenticatesAdmins;
    use UploadsFiles;

    /** @var array<string, string> */
    private array $owner;

    private Skill $skill;

    private Evidence $evidence;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeStorage();
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
        $this->skill = Skill::factory()->public()->create(['slug' => 'laravel']);
        $this->evidence = Evidence::factory()->public()->create();
        $this->evidence->skills()->attach($this->skill->id);
    }

    public function test_the_skill_page_lists_available_files_without_folder_paths(): void
    {
        $pdf = $this->uploadFile($this->owner, 'certificate.pdf', 2048);
        $trashed = $this->uploadFile($this->owner, 'old.pdf');
        $this->evidence->files()->attach([$pdf => ['position' => 0], $trashed => ['position' => 1]]);
        $this->call('POST', '/api/admin/media/delete', ['ids' => [$trashed]], $this->owner)->assertOk();

        $this->getJson('/api/public/skills/laravel')->assertOk()
            ->assertJsonPath('data.data.evidence.0.files', [[
                'key' => $this->keyOf($this->evidence, $pdf),
                'name' => 'certificate.pdf',
                'size' => 2048,
                'mime_type' => 'application/octet-stream',
            ]]);
    }

    public function test_evidence_without_a_link_is_shown_with_its_files(): void
    {
        $this->evidence->update(['url' => null]);
        $this->evidence->files()->attach($this->uploadFile($this->owner, 'certificate.pdf'));

        $this->getJson('/api/public/skills/laravel')->assertOk()
            ->assertJsonPath('data.data.evidence.0.url', null)
            ->assertJsonPath('data.data.evidence.0.files.0.name', 'certificate.pdf');
    }

    public function test_a_file_of_public_evidence_gets_a_short_lived_link(): void
    {
        $pdf = $this->uploadFile($this->owner, 'certificate.pdf');
        $zip = $this->uploadFile($this->owner, 'source.zip');
        $this->evidence->files()->attach([$pdf, $zip]);

        // Shown in the browser with the type the server picked; anything else downloads
        $this->getJson('/api/public/files/'.$this->keyOf($this->evidence, $pdf))->assertOk()
            ->assertJsonPath('data.url', fn (string $url): bool => str_contains($url, 'inline=application%2Fpdf')
                && str_contains($url, 'X-Amz-Expires='.MediaConst::PREVIEW_URL_TTL_SECONDS));
        $this->getJson('/api/public/files/'.$this->keyOf($this->evidence, $zip))->assertOk()
            ->assertJsonPath('data.url', fn (string $url): bool => ! str_contains($url, 'inline=')
                && str_contains($url, 'X-Amz-Expires='.MediaConst::DOWNLOAD_URL_TTL_SECONDS));
    }

    public function test_files_not_on_the_public_ledger_are_not_found(): void
    {
        $privateEvidence = Evidence::factory()->create();
        $privateEvidence->skills()->attach($this->skill->id);
        $privateEvidence->files()->attach($this->uploadFile($this->owner, 'draft.txt'));

        $hiddenNote = Evidence::factory()->public()->fromObsidian()->create(['unpublished_at' => now()]);
        $hiddenNote->skills()->attach($this->skill->id);
        $hiddenNote->files()->attach($this->uploadFile($this->owner, 'hidden.txt'));

        $evidenceOfPrivateSkill = Evidence::factory()->public()->create();
        $evidenceOfPrivateSkill->skills()->attach(Skill::factory()->create()->id);
        $evidenceOfPrivateSkill->files()->attach($this->uploadFile($this->owner, 'rust.txt'));

        $trashed = $this->uploadFile($this->owner, 'trashed.txt');
        $this->evidence->files()->attach($trashed);
        $trashedKey = $this->keyOf($this->evidence, $trashed);
        $this->call('POST', '/api/admin/media/delete', ['ids' => [$trashed]], $this->owner)->assertOk();

        // Detached: the old link stops working even though the file is still in the file manager
        $detached = $this->uploadFile($this->owner, 'detached.txt');
        $this->evidence->files()->attach($detached);
        $detachedKey = $this->keyOf($this->evidence, $detached);
        $this->evidence->files()->detach($detached);

        $keys = [
            $this->keyOf($privateEvidence),
            $this->keyOf($hiddenNote),
            $this->keyOf($evidenceOfPrivateSkill),
            $trashedKey,
            $detachedKey,
            (string) Str::uuid(),
        ];
        foreach ($keys as $key) {
            $this->getJson("/api/public/files/{$key}")->assertNotFound();
        }
        // Not a key at all: no route
        $this->getJson('/api/public/files/1')->assertNotFound();
    }

    private function keyOf(Evidence $evidence, ?int $mediaId = null): string
    {
        return (string) DB::table('evidence_media')
            ->where('evidence_id', $evidence->id)
            ->when($mediaId !== null, fn ($query) => $query->where('media_id', $mediaId))
            ->value('public_id');
    }
}
