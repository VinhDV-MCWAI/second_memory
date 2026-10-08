<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Constants\CommonVal;
use App\Enums\GoalStatus;
use App\Enums\SkillLevel as SkillLevelEnum;
use App\Models\Audit\AuditLog;
use App\Models\Ledger\LearningGoal;
use App\Models\Ledger\Skill;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * RFC-002 slice 4: learning goals (REQ-002 US-6). The auto-achieve rule itself is in SkillLevelApiTest.
 */
final class LearningGoalApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const BASE = '/api/admin/learning-goal';

    /** @var array<string, string> */
    private array $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->loginAsOwner(AdminMst::factory()->create());
    }

    public function test_store_creates_an_open_goal_and_audits_it(): void
    {
        $skill = Skill::factory()->atLevel(SkillLevelEnum::LEARNING)->create();

        $response = $this->call('POST', self::BASE.'/store', [
            'skill_id' => $skill->id, 'target_level' => 3, 'target_date' => '2026-12-31', 'note' => 'After P3', 'status' => 'achieved',
        ], $this->owner);

        $response->assertOk();
        $goal = LearningGoal::query()->findOrFail($response->json('data'));
        $this->assertSame(GoalStatus::OPEN, $goal->status);
        $this->assertSame(SkillLevelEnum::INDEPENDENT, $goal->target_level);
        $this->assertSame(1, AuditLog::query()->where(['auditable_type' => 'learning_goal', 'auditable_id' => $goal->id, 'event' => 'created'])->count());
    }

    public function test_store_rejects_a_target_the_skill_already_reached(): void
    {
        $skill = Skill::factory()->atLevel(SkillLevelEnum::INDEPENDENT)->create();

        foreach ([3, 2, 5] as $target) {
            $this->call('POST', self::BASE.'/store', ['skill_id' => $skill->id, 'target_level' => $target], $this->owner)
                ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
                ->assertJsonStructure(['error' => ['messages' => ['target_level']]]);
        }
        $this->assertSame(0, LearningGoal::query()->count());
    }

    public function test_list_returns_the_contract_fields_and_filters_by_status(): void
    {
        $skill = Skill::factory()->create();
        $open = LearningGoal::factory()->for($skill)->create(['target_date' => '2026-12-31']);
        LearningGoal::factory()->for($skill)->create(['status' => GoalStatus::DROPPED]);

        $this->call('GET', self::BASE.'/list', ['status' => 'open', 'skill_id' => $skill->id], $this->owner)
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $open->id)
            ->assertJsonPath('data.data.0.skill', ['id' => $skill->id, 'name' => $skill->name, 'current_level' => 1])
            ->assertJsonPath('data.data.0.target_date', '2026-12-31')
            ->assertJsonStructure(['data' => ['data' => [[
                'id', 'skill', 'target_level', 'target_level_label', 'target_date', 'status', 'achieved_on', 'note', 'created_at', 'updated_at',
            ]]]]);
    }

    public function test_dropped_goal_stays_listed_and_is_not_achieved_by_a_level_change(): void
    {
        $skill = Skill::factory()->create();
        $goal = LearningGoal::factory()->for($skill)->create(['target_level' => SkillLevelEnum::INDEPENDENT]);

        $this->call('PUT', self::BASE."/update/{$goal->id}", ['status' => 'dropped'], $this->owner)->assertOk();
        $this->call('POST', '/api/admin/skill-level/store', ['skill_id' => $skill->id, 'level' => 4], $this->owner)->assertOk();

        $this->assertSame(GoalStatus::DROPPED, $goal->refresh()->status);
        $this->assertNull($goal->achieved_on);
        $this->call('GET', self::BASE.'/list', [], $this->owner)->assertJsonPath('data.data.0.status', 'dropped');
    }

    public function test_status_cannot_be_set_to_achieved_by_hand(): void
    {
        $goal = LearningGoal::factory()->create();

        $this->call('PUT', self::BASE."/update/{$goal->id}", ['status' => 'achieved'], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['status']]]);
    }

    public function test_reopening_or_lowering_needs_a_target_above_the_current_level(): void
    {
        $skill = Skill::factory()->atLevel(SkillLevelEnum::INDEPENDENT)->create();
        $dropped = LearningGoal::factory()->for($skill)->create(['target_level' => SkillLevelEnum::INDEPENDENT, 'status' => GoalStatus::DROPPED]);
        $open = LearningGoal::factory()->for($skill)->create(['target_level' => SkillLevelEnum::CAN_TEACH]);

        $this->call('PUT', self::BASE."/update/{$dropped->id}", ['status' => 'open'], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('PUT', self::BASE."/update/{$open->id}", ['target_level' => 2], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('PUT', self::BASE."/update/{$dropped->id}", ['status' => 'open', 'target_level' => 4], $this->owner)
            ->assertOk();
        $this->assertSame(GoalStatus::OPEN, $dropped->refresh()->status);
    }

    public function test_achieved_goal_changes_only_its_date_and_note(): void
    {
        $goal = LearningGoal::factory()->create(['status' => GoalStatus::ACHIEVED, 'achieved_on' => '2026-10-01']);

        $this->call('PUT', self::BASE."/update/{$goal->id}", ['note' => 'Done early', 'target_date' => '2026-10-01', 'status' => 'achieved'], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->call('PUT', self::BASE."/update/{$goal->id}", ['status' => 'open'], $this->owner)
            ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
            ->assertJsonStructure(['error' => ['messages' => ['status']]]);
        $this->call('PUT', self::BASE."/update/{$goal->id}", ['note' => 'Done early', 'target_level' => $goal->target_level->value], $this->owner)
            ->assertOk();

        $goal->refresh();
        $this->assertSame('Done early', $goal->note);
        $this->assertSame(GoalStatus::ACHIEVED, $goal->status);
    }

    public function test_viewer_can_read_but_not_write(): void
    {
        LearningGoal::factory()->create();
        $viewer = $this->loginAs(AdminMst::factory()->create());

        $this->call('GET', self::BASE.'/list', [], $viewer)->assertOk()->assertJsonCount(1, 'data.data');
        $this->call('POST', self::BASE.'/delete', ['ids' => [1]], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
    }

    public function test_update_of_a_missing_goal_is_404_and_deleting_a_skill_removes_its_goals(): void
    {
        $goal = LearningGoal::factory()->create();

        $this->call('PUT', self::BASE.'/update/999999', ['note' => 'x'], $this->owner)->assertStatus(CommonVal::HTTP_NOT_FOUND);
        $this->call('POST', '/api/admin/skill/delete', ['ids' => [$goal->skill_id]], $this->owner)->assertOk();

        $this->assertDatabaseMissing('learning_goal', ['id' => $goal->id]);
    }

    public function test_delete_is_audited(): void
    {
        $goal = LearningGoal::factory()->create();

        $this->call('POST', self::BASE.'/delete', ['ids' => [$goal->id]], $this->owner)->assertOk();

        $this->assertDatabaseMissing('learning_goal', ['id' => $goal->id]);
        $this->assertSame(1, AuditLog::query()->where(['auditable_type' => 'learning_goal', 'auditable_id' => $goal->id, 'event' => 'deleted'])->count());
    }
}
