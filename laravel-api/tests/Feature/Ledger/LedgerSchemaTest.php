<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Enums\SkillLevel as SkillLevelEnum;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\LearningGoal;
use App\Models\Ledger\Skill;
use App\Models\Ledger\SkillLevel;
use App\Models\Ledger\Tag;
use Illuminate\Database\QueryException;
use Tests\TestCase;

/**
 * RFC-002 slice 2: the database keeps the Skill Ledger rules that do not depend on PHP.
 */
final class LedgerSchemaTest extends TestCase
{
    public function test_factory_skill_starts_with_one_history_entry_matching_current_level(): void
    {
        $skill = Skill::factory()->atLevel(SkillLevelEnum::INDEPENDENT)->create();

        $this->assertSame(1, $skill->levels()->count());
        $this->assertSame(SkillLevelEnum::INDEPENDENT, $skill->levels()->first()?->level);
    }

    public function test_skill_names_are_unique_ignoring_case(): void
    {
        Skill::factory()->create(['name' => 'PostgreSQL', 'slug' => 'postgresql']);

        $this->expectException(QueryException::class);
        Skill::factory()->create(['name' => 'postgresql', 'slug' => 'postgresql-2']);
    }

    public function test_tag_names_are_unique_ignoring_case(): void
    {
        Tag::factory()->create(['name' => 'Laravel']);

        $this->expectException(QueryException::class);
        Tag::factory()->create(['name' => 'LARAVEL']);
    }

    public function test_level_outside_the_scale_is_rejected_by_the_database(): void
    {
        $skill = Skill::factory()->create();

        $this->expectException(QueryException::class);
        SkillLevel::query()->insert(['skill_id' => $skill->id, 'level' => 5, 'changed_on' => now()->toDateString()]);
    }

    public function test_evidence_links_several_skills_and_tags(): void
    {
        $skills = Skill::factory()->count(2)->create();
        $tag = Tag::factory()->create();
        $evidence = Evidence::factory()->create();

        $evidence->skills()->attach($skills->pluck('id'));
        $evidence->tags()->attach($tag->id);

        $this->assertSame(2, $evidence->skills()->count());
        $this->assertSame(1, $skills->first()?->evidence()->count());
        $this->assertSame(1, $tag->evidence()->count());
    }

    public function test_deleting_a_skill_removes_its_history_goals_and_links_but_keeps_evidence_and_tags(): void
    {
        $skill = Skill::factory()->create();
        $tag = Tag::factory()->create();
        $evidence = Evidence::factory()->create();
        $skill->tags()->attach($tag->id);
        $evidence->skills()->attach($skill->id);
        LearningGoal::factory()->create(['skill_id' => $skill->id]);

        $skill->delete();

        $this->assertDatabaseMissing('skill_level', ['skill_id' => $skill->id]);
        $this->assertDatabaseMissing('learning_goal', ['skill_id' => $skill->id]);
        $this->assertDatabaseMissing('skill_tag', ['skill_id' => $skill->id]);
        $this->assertDatabaseMissing('evidence_skill', ['skill_id' => $skill->id]);
        $this->assertDatabaseHas('evidence', ['id' => $evidence->id]);
        $this->assertDatabaseHas('tag', ['id' => $tag->id]);
    }

    public function test_external_key_of_imported_notes_is_unique(): void
    {
        Evidence::factory()->fromObsidian()->create(['external_key' => 'notes/a.md']);

        $this->expectException(QueryException::class);
        Evidence::factory()->fromObsidian()->create(['external_key' => 'notes/a.md']);
    }
}
