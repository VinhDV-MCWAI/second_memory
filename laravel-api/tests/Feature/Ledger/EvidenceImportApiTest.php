<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Constants\CommonVal;
use App\Constants\LedgerConst;
use App\Enums\EvidenceSource;
use App\Enums\EvidenceType;
use App\Enums\SkillLevel;
use App\Models\Audit\AuditLog;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\Skill;
use App\Models\Ledger\Tag;
use App\Models\Master\AdminMst;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * RFC-002 §4.5: idempotent sync of published Obsidian notes (REQ-002 US-4).
 */
final class EvidenceImportApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const URI = '/api/admin/evidence/import';

    private AdminMst $admin;

    /** @var array<string, string> */
    private array $owner;

    private Skill $laravel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminMst::factory()->create();
        $this->owner = $this->loginAsOwner($this->admin);
        $this->laravel = Skill::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function note(string $key = 'lab/audit-log.md', array $overrides = []): array
    {
        return [
            'external_key' => $key,
            'title' => 'Audit log expand/contract',
            'url' => 'https://notes.example.com/lab/audit-log',
            'occurred_on' => '2026-10-01',
            'summary' => 'One audit table for every change.',
            'tags' => ['lab/p3'],
            'skills' => ['laravel'],
            ...$overrides,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $notes
     */
    private function import(array $notes, bool $dryRun = false): TestResponse
    {
        return $this->call('POST', self::URI, ['notes' => $notes, 'dry_run' => $dryRun], $this->owner);
    }

    /**
     * @return array{created: int, updated: int, unchanged: int, hidden: int}
     */
    private function counts(TestResponse $response): array
    {
        $response->assertOk();

        return [
            'created' => $response->json('data.created'),
            'updated' => $response->json('data.updated'),
            'unchanged' => $response->json('data.unchanged'),
            'hidden' => $response->json('data.hidden'),
        ];
    }

    public function test_a_published_note_becomes_public_note_evidence_linked_to_its_skill(): void
    {
        $response = $this->import([$this->note(overrides: ['skills' => ['laravel', 'Kotlin', 'kotlin']])]);

        $this->assertSame(['created' => 1, 'updated' => 0, 'unchanged' => 0, 'hidden' => 0], $this->counts($response));
        $response->assertJsonPath('data.unknown_skills', ['Kotlin']);

        $evidence = Evidence::query()->with(['skills', 'tags'])->sole();
        $this->assertSame(EvidenceType::NOTE, $evidence->type);
        $this->assertSame(EvidenceSource::OBSIDIAN, $evidence->source);
        $this->assertTrue($evidence->is_public);
        $this->assertSame('Audit log expand/contract', $evidence->title);
        $this->assertSame('2026-10-01', $evidence->occurred_on->format(LedgerConst::DATE_FORMAT));
        $this->assertSame([$this->laravel->id], $evidence->skills->pluck('id')->all());
        $this->assertSame(['lab/p3'], $evidence->tags->pluck('name')->all());
        $this->assertDatabaseHas('evidence', ['id' => $evidence->id, 'external_key' => 'lab/audit-log.md']);
        // Unknown skills are reported, never created
        $this->assertSame(0, Skill::query()->whereRaw("lower(name) = 'kotlin'")->count());
    }

    public function test_writes_are_audited_with_the_admin_as_actor_and_marked_as_imported(): void
    {
        $this->import([$this->note()])->assertOk();

        $evidence = Evidence::query()->sole();
        $created = AuditLog::query()->where(['auditable_type' => 'evidence', 'auditable_id' => $evidence->id, 'event' => 'created'])->sole();
        $this->assertSame($this->admin->id, $created->admin_mst_id);
        $this->assertSame(LedgerConst::IMPORT_AUDIT_VIA, $created->new_values['via']);
        $this->assertSame('lab/audit-log.md', $created->new_values['external_key']);
        $tag = AuditLog::query()->where(['auditable_type' => 'tag', 'event' => 'created'])->sole();
        $this->assertSame(['name' => 'lab/p3', 'via' => LedgerConst::IMPORT_AUDIT_VIA], $tag->new_values);
    }

    public function test_a_second_run_changes_nothing(): void
    {
        $notes = [$this->note(), $this->note('lab/search.md', ['title' => 'Search', 'tags' => ['LAB/P3', 'postgres'], 'summary' => null])];
        $this->import($notes)->assertOk();
        $before = Evidence::query()->orderBy('id')->get(['id', 'updated_at'])->toArray();
        $auditRows = AuditLog::query()->count();
        $this->travel(5)->minutes();

        $response = $this->import($notes);

        $this->assertSame(['created' => 0, 'updated' => 0, 'unchanged' => 2, 'hidden' => 0], $this->counts($response));
        $this->assertSame($before, Evidence::query()->orderBy('id')->get(['id', 'updated_at'])->toArray());
        $this->assertSame($auditRows, AuditLog::query()->count());
        // Tag names match ignoring case: "LAB/P3" reuses "lab/p3"
        $this->assertSame(2, Tag::query()->count());
    }

    public function test_a_changed_note_is_updated_but_the_owners_visibility_choice_survives(): void
    {
        $this->import([$this->note()])->assertOk();
        $evidence = Evidence::query()->sole();
        $evidence->update(['is_public' => false]);

        $response = $this->import([$this->note(overrides: ['title' => 'Audit log, take two', 'skills' => []])]);

        $this->assertSame(['created' => 0, 'updated' => 1, 'unchanged' => 0, 'hidden' => 0], $this->counts($response));
        $evidence->refresh();
        $this->assertSame('Audit log, take two', $evidence->title);
        $this->assertFalse($evidence->is_public);
        $this->assertSame(0, $evidence->skills()->count());
        $audit = AuditLog::query()->where(['auditable_id' => $evidence->id, 'event' => 'updated'])->sole();
        $this->assertSame(['title' => 'Audit log, take two', 'skills' => [], 'via' => LedgerConst::IMPORT_AUDIT_VIA], $audit->new_values);
    }

    public function test_a_removed_note_is_hidden_once_and_a_republished_one_stays_private(): void
    {
        $this->import([$this->note(), $this->note('lab/search.md')])->assertOk();

        $response = $this->import([$this->note('lab/search.md')]);

        $this->assertSame(['created' => 0, 'updated' => 0, 'unchanged' => 1, 'hidden' => 1], $this->counts($response));
        $hidden = Evidence::query()->where('external_key', 'lab/audit-log.md')->sole();
        $this->assertFalse($hidden->is_public);
        $this->assertNotNull($hidden->unpublished_at);
        $this->assertSame(['created' => 0, 'updated' => 0, 'unchanged' => 1, 'hidden' => 0], $this->counts($this->import([$this->note('lab/search.md')])));

        $response = $this->import([$this->note(), $this->note('lab/search.md')]);

        $this->assertSame(['created' => 0, 'updated' => 1, 'unchanged' => 1, 'hidden' => 0], $this->counts($response));
        $hidden->refresh();
        $this->assertNull($hidden->unpublished_at);
        $this->assertFalse($hidden->is_public);
    }

    public function test_an_empty_list_hides_every_imported_row_and_never_touches_manual_evidence(): void
    {
        $manual = Evidence::factory()->public()->create();
        $this->import([$this->note()])->assertOk();

        $response = $this->import([]);

        $this->assertSame(['created' => 0, 'updated' => 0, 'unchanged' => 0, 'hidden' => 1], $this->counts($response));
        $this->assertSame(2, Evidence::query()->count());
        $this->assertTrue($manual->refresh()->is_public);
        $this->assertNull($manual->unpublished_at);
    }

    public function test_the_import_never_changes_skill_levels(): void
    {
        $this->laravel->update(['current_level' => SkillLevel::CAN_TEACH]);
        $levels = $this->laravel->levels()->count();

        $this->import([$this->note()])->assertOk();
        $this->import([$this->note(overrides: ['title' => 'Changed'])])->assertOk();

        $this->laravel->refresh();
        $this->assertSame(SkillLevel::CAN_TEACH, $this->laravel->current_level);
        $this->assertSame($levels, $this->laravel->levels()->count());
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $this->import([$this->note()])->assertOk();
        $auditRows = AuditLog::query()->count();

        $response = $this->import([$this->note('lab/new.md', ['tags' => ['brand-new']]), $this->note(overrides: ['title' => 'New title'])], dryRun: true);

        $this->assertSame(['created' => 1, 'updated' => 1, 'unchanged' => 0, 'hidden' => 0], $this->counts($response));
        $this->assertSame(1, Evidence::query()->count());
        $this->assertSame('Audit log expand/contract', Evidence::query()->sole()->title);
        $this->assertSame(0, Tag::query()->where('name', 'brand-new')->count());
        $this->assertSame($auditRows, AuditLog::query()->count());
    }

    public function test_invalid_notes_are_rejected_and_nothing_is_written(): void
    {
        $tomorrow = Carbon::tomorrow()->format(LedgerConst::DATE_FORMAT);
        $cases = [
            ['notes.0.url', [$this->note(overrides: ['url' => 'obsidian://open?file=a'])]],
            ['notes.0.occurred_on', [$this->note(overrides: ['occurred_on' => $tomorrow])]],
            ['notes.0.occurred_on', [$this->note(overrides: ['occurred_on' => null])]],
            ['notes.0.title', [$this->note(overrides: ['title' => str_repeat('a', LedgerConst::EVIDENCE_TITLE_MAX + 1)])]],
            ['notes.1.external_key', [$this->note(), $this->note()]],
        ];
        foreach ($cases as [$field, $notes]) {
            $this->import($notes)
                ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
                ->assertJsonStructure(['error' => ['messages' => [$field]]]);
        }
        $this->call('POST', self::URI, [], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['notes']]]);
        $this->assertSame(0, Evidence::query()->count());
    }

    public function test_a_viewer_session_cannot_import_and_a_guest_is_unauthenticated(): void
    {
        $viewer = $this->loginAs(AdminMst::factory()->create());
        $this->call('POST', self::URI, ['notes' => [$this->note()]], $viewer)->assertForbidden();

        $this->flushSession();
        $this->postJson(self::URI, ['notes' => [$this->note()]])->assertUnauthorized();
        $this->assertSame(0, Evidence::query()->count());
    }
}
