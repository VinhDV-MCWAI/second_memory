<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Constants\CommonVal;
use App\Enums\EvidenceSource;
use App\Enums\EvidenceType;
use App\Models\Audit\AuditLog;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\Skill;
use App\Models\Ledger\Tag;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * RFC-002 slice 4: evidence links (REQ-002 US-2).
 */
final class EvidenceApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const BASE = '/api/admin/evidence';

    /** @var array<string, string> */
    private array $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'type' => EvidenceType::PR->value,
            'title' => 'Audit log expand/contract',
            'url' => 'https://github.com/example/second-memory/pull/12',
            'occurred_on' => '2026-10-07',
            'summary' => 'One audit table',
            ...$overrides,
        ];
    }

    public function test_one_evidence_appears_on_several_skills_and_is_audited(): void
    {
        $laravel = Skill::factory()->create();
        $postgres = Skill::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->call('POST', self::BASE.'/store', $this->payload([
            'skill_ids' => [$laravel->id, $postgres->id], 'tag_ids' => [$tag->id],
        ]), $this->owner);

        $response->assertOk();
        $evidence = Evidence::query()->findOrFail($response->json('data'));
        $this->assertSame(EvidenceSource::MANUAL, $evidence->source);
        $this->assertFalse($evidence->is_public);
        $this->assertSame([$evidence->id], $laravel->evidence()->pluck('evidence.id')->all());
        $this->assertSame([$evidence->id], $postgres->evidence()->pluck('evidence.id')->all());
        $audit = AuditLog::query()->where(['auditable_type' => 'evidence', 'auditable_id' => $evidence->id, 'event' => 'created'])->firstOrFail();
        $this->assertCount(2, $audit->new_values['skills']);
    }

    public function test_store_rejects_invalid_input(): void
    {
        $skill = Skill::factory()->create();

        $cases = [
            ['url', ['url' => 'javascript:alert(1)']],
            ['url', ['url' => 'ftp://example.com/file']],
            ['skill_ids', ['skill_ids' => []]],
            ['skill_ids.0', ['skill_ids' => [999999]]],
            ['type', ['type' => 'video']],
            ['occurred_on', ['occurred_on' => '07/10/2026']],
        ];
        foreach ($cases as [$field, $override]) {
            $this->call('POST', self::BASE.'/store', $this->payload(['skill_ids' => [$skill->id], ...$override]), $this->owner)
                ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
                ->assertJsonStructure(['error' => ['messages' => [$field]]]);
        }
        $this->call('POST', self::BASE.'/store', $this->payload(), $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['skill_ids']]]);
        $this->assertSame(0, Evidence::query()->count());
    }

    public function test_importer_fields_cannot_be_written_through_the_admin_api(): void
    {
        $skill = Skill::factory()->create();

        $id = $this->call('POST', self::BASE.'/store', $this->payload([
            'skill_ids' => [$skill->id], 'source' => 'obsidian', 'external_key' => 'notes/x.md', 'unpublished_at' => '2026-10-01',
        ]), $this->owner)->assertOk()->json('data');

        $evidence = Evidence::query()->findOrFail($id);
        $this->assertSame(EvidenceSource::MANUAL, $evidence->source);
        $this->assertNull($evidence->unpublished_at);
        $this->assertDatabaseHas('evidence', ['id' => $id, 'external_key' => null]);
    }

    public function test_list_returns_the_contract_fields_and_filters_by_skill_and_type(): void
    {
        $skill = Skill::factory()->create();
        $linked = Evidence::factory()->create(['type' => EvidenceType::ADR, 'occurred_on' => '2026-10-02']);
        $linked->skills()->attach($skill->id);
        Evidence::factory()->create(['type' => EvidenceType::ADR]);

        $response = $this->call('GET', self::BASE.'/list', ['skill_id' => $skill->id, 'type' => 'adr'], $this->owner);

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $linked->id)
            ->assertJsonPath('data.data.0.occurred_on', '2026-10-02')
            ->assertJsonPath('data.data.0.skills.0', ['id' => $skill->id, 'name' => $skill->name])
            ->assertJsonStructure(['data' => ['data' => [[
                'id', 'type', 'title', 'url', 'occurred_on', 'summary', 'is_public', 'source',
                'unpublished_at', 'skills', 'tags', 'created_at', 'updated_at',
            ]]]]);
    }

    public function test_update_replaces_links_and_records_only_changed_fields(): void
    {
        $old = Skill::factory()->create();
        $new = Skill::factory()->create();
        $evidence = Evidence::factory()->create();
        $evidence->skills()->attach($old->id);

        $this->call('PUT', self::BASE."/update/{$evidence->id}", ['skill_ids' => [$new->id], 'is_public' => true], $this->owner)
            ->assertOk();

        $this->assertSame([$new->id], $evidence->skills()->pluck('skill.id')->all());
        $this->assertTrue($evidence->refresh()->is_public);
        $audit = AuditLog::query()->where(['auditable_type' => 'evidence', 'event' => 'updated'])->firstOrFail();
        $this->assertSame(['is_public', 'skills'], array_keys($audit->new_values));
    }

    public function test_imported_evidence_accepts_type_and_visibility_but_not_vault_fields(): void
    {
        $skill = Skill::factory()->create();
        $note = Evidence::factory()->fromObsidian()->create(['occurred_on' => '2026-09-01']);
        $note->skills()->attach($skill->id);
        $url = '/update/'.$note->id;

        // A form posting every field back unchanged is fine
        $this->call('PUT', self::BASE.$url, [
            'title' => $note->title, 'url' => $note->url, 'occurred_on' => '2026-09-01', 'summary' => $note->summary,
            'skill_ids' => [(string) $skill->id], 'tag_ids' => [], 'type' => 'adr', 'is_public' => true,
        ], $this->owner)->assertOk();
        $this->assertSame(EvidenceType::ADR, $note->refresh()->type);

        $this->call('PUT', self::BASE.$url, ['title' => 'Edited in admin', 'skill_ids' => [Skill::factory()->create()->id]], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['title', 'skill_ids']]]);
        $this->assertNotSame('Edited in admin', $note->refresh()->title);
    }

    public function test_viewer_can_read_but_not_write(): void
    {
        Evidence::factory()->create();
        $viewer = $this->loginAs(AdminMst::factory()->create());

        $this->call('GET', self::BASE.'/list', [], $viewer)->assertOk()->assertJsonCount(1, 'data.data');
        $this->call('POST', self::BASE.'/store', $this->payload(), $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    public function test_update_of_missing_evidence_is_404(): void
    {
        $this->call('PUT', self::BASE.'/update/999999', ['title' => 'Nope'], $this->owner)
            ->assertStatus(CommonVal::HTTP_NOT_FOUND);
    }

    public function test_delete_removes_links_keeps_skills_and_is_audited(): void
    {
        $skill = Skill::factory()->create();
        $evidence = Evidence::factory()->create();
        $evidence->skills()->attach($skill->id);

        $this->call('POST', self::BASE.'/delete', ['ids' => [$evidence->id]], $this->owner)->assertOk();

        $this->assertDatabaseMissing('evidence', ['id' => $evidence->id]);
        $this->assertDatabaseMissing('evidence_skill', ['evidence_id' => $evidence->id]);
        $this->assertDatabaseHas('skill', ['id' => $skill->id]);
        $this->assertSame(1, AuditLog::query()->where(['auditable_type' => 'evidence', 'auditable_id' => $evidence->id, 'event' => 'deleted'])->count());
    }
}
