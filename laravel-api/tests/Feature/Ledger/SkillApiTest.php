<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Constants\CommonVal;
use App\Enums\SkillLevel as SkillLevelEnum;
use App\Models\Audit\AuditLog;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\Skill;
use App\Models\Ledger\Tag;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * RFC-002 slice 3: skills (REQ-002 US-1).
 */
final class SkillApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const BASE = '/api/admin/skill';

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
            'name' => 'Kỹ năng thiết kế database',
            'category' => 'database',
            'description' => 'Schema design',
            'level' => SkillLevelEnum::LEARNING->value,
            'changed_on' => '2026-10-01',
            'reason' => 'Started RFC-002',
            ...$overrides,
        ];
    }

    public function test_requires_a_session(): void
    {
        $this->flushSession();

        $this->getJson(self::BASE.'/list')->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }

    public function test_store_creates_the_skill_with_its_first_level_entry_and_audits_both(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->call('POST', self::BASE.'/store', $this->payload(['tag_ids' => [$tag->id]]), $this->owner);

        $response->assertOk();
        $skill = Skill::query()->findOrFail($response->json('data'));
        $this->assertSame('ky-nang-thiet-ke-database', $skill->slug);
        $this->assertFalse($skill->is_public);
        $this->assertSame(SkillLevelEnum::LEARNING, $skill->current_level);
        $this->assertSame([$tag->id], $skill->tags()->pluck('tag.id')->all());
        $this->assertSame(1, $skill->levels()->count());
        $this->assertSame('Started RFC-002', $skill->levels()->first()?->reason);
        $this->assertSame(1, AuditLog::query()->where(['auditable_type' => 'skill', 'auditable_id' => $skill->id, 'event' => 'created'])->count());
        $this->assertSame(1, AuditLog::query()->where(['auditable_type' => 'skill_level', 'event' => 'created'])->count());
    }

    public function test_store_rejects_invalid_input(): void
    {
        Skill::factory()->create(['name' => 'PostgreSQL', 'slug' => 'postgresql']);

        $cases = [
            'level' => ['level' => 5],
            'name' => ['name' => 'postgresql'],
            'changed_on' => ['changed_on' => now()->addDay()->format('Y-m-d')],
            'category' => ['category' => ''],
        ];
        foreach ($cases as $field => $override) {
            $this->call('POST', self::BASE.'/store', $this->payload($override), $this->owner)
                ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
                ->assertJsonStructure(['error' => ['messages' => [$field]]]);
        }
        $this->assertSame(1, Skill::query()->count());
    }

    public function test_slug_clash_gets_a_suffix(): void
    {
        $first = $this->call('POST', self::BASE.'/store', $this->payload(['name' => 'C']), $this->owner)->json('data');
        $second = $this->call('POST', self::BASE.'/store', $this->payload(['name' => 'C#']), $this->owner)->json('data');

        $this->assertSame('c', Skill::query()->findOrFail($first)->slug);
        $this->assertSame('c-2', Skill::query()->findOrFail($second)->slug);
    }

    public function test_viewer_can_read_but_not_write(): void
    {
        Skill::factory()->create();
        $viewer = $this->loginAs(AdminMst::factory()->create());

        $this->call('GET', self::BASE.'/list', [], $viewer)->assertOk()->assertJsonCount(1, 'data.data');
        $this->call('POST', self::BASE.'/store', $this->payload(), $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    public function test_list_returns_the_contract_fields_and_filters_by_tag_and_visibility(): void
    {
        $tag = Tag::factory()->create();
        $tagged = Skill::factory()->public()->create();
        $tagged->tags()->attach($tag->id);
        Skill::factory()->create();

        $response = $this->call('GET', self::BASE.'/list', ['tag_id' => $tag->id, 'is_public' => 1], $this->owner);

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $tagged->id)
            ->assertJsonPath('data.data.0.current_level_label', 'Learning')
            ->assertJsonPath('data.data.0.tags.0.name', $tag->name)
            ->assertJsonStructure(['data' => ['data' => [[
                'id', 'name', 'slug', 'category', 'description', 'is_public',
                'current_level', 'current_level_label', 'tags', 'created_at', 'updated_at',
            ]]]]);
    }

    public function test_update_cannot_change_the_level_or_the_slug(): void
    {
        $skill = Skill::factory()->create(['name' => 'Go', 'slug' => 'go']);

        $this->call('PUT', self::BASE."/update/{$skill->id}", [
            'name' => 'Golang', 'level' => 4, 'current_level' => 4, 'slug' => 'hacked',
        ], $this->owner)->assertOk();

        $skill->refresh();
        $this->assertSame('Golang', $skill->name);
        $this->assertSame('go', $skill->slug);
        $this->assertSame(SkillLevelEnum::LEARNING, $skill->current_level);
        $this->assertSame(1, $skill->levels()->count());
        $this->assertSame(['name' => 'Golang'], AuditLog::query()->where(['auditable_type' => 'skill', 'event' => 'updated'])->firstOrFail()->new_values);
    }

    public function test_update_keeps_its_own_name_and_rejects_another_skills_name(): void
    {
        $skill = Skill::factory()->create(['name' => 'Redis', 'slug' => 'redis']);
        Skill::factory()->create(['name' => 'Docker', 'slug' => 'docker']);

        $this->call('PUT', self::BASE."/update/{$skill->id}", ['name' => 'REDIS'], $this->owner)->assertOk();
        $this->call('PUT', self::BASE."/update/{$skill->id}", ['name' => 'docker'], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
    }

    public function test_update_of_a_missing_skill_is_404(): void
    {
        $this->call('PUT', self::BASE.'/update/999999', ['name' => 'Nope'], $this->owner)
            ->assertStatus(CommonVal::HTTP_NOT_FOUND);
    }

    public function test_delete_removes_history_and_links_keeps_evidence_and_is_audited(): void
    {
        $skill = Skill::factory()->create();
        $evidence = Evidence::factory()->create();
        $evidence->skills()->attach($skill->id);

        $this->call('POST', self::BASE.'/delete', ['ids' => [$skill->id]], $this->owner)->assertOk();

        $this->assertDatabaseMissing('skill', ['id' => $skill->id]);
        $this->assertDatabaseMissing('skill_level', ['skill_id' => $skill->id]);
        $this->assertDatabaseHas('evidence', ['id' => $evidence->id]);
        $this->assertSame(1, AuditLog::query()->where(['auditable_type' => 'skill', 'auditable_id' => $skill->id, 'event' => 'deleted'])->count());
    }
}
