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
 * RFC-002 slice 3: append-only level history (REQ-002 US-1) and goal auto-achieve (US-6).
 */
final class SkillLevelApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const BASE = '/api/admin/skill-level';

    private AdminMst $admin;

    /** @var array<string, string> */
    private array $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminMst::factory()->create();
        $this->owner = $this->loginAsOwner($this->admin);
    }

    public function test_two_changes_give_three_entries_newest_first_and_the_latest_level(): void
    {
        $skill = Skill::factory()->create();
        $skill->levels()->update(['changed_on' => '2026-08-01']);

        $this->call('POST', self::BASE.'/store', ['skill_id' => $skill->id, 'level' => 2, 'changed_on' => '2026-09-01', 'reason' => 'Shipped P3'], $this->owner)->assertOk();
        $this->call('POST', self::BASE.'/store', ['skill_id' => $skill->id, 'level' => 3, 'changed_on' => '2026-10-01', 'reason' => 'Led the migration'], $this->owner)->assertOk();

        $response = $this->call('GET', self::BASE.'/list', ['skill_id' => $skill->id], $this->owner);

        $response->assertOk()
            ->assertJsonCount(3, 'data.data')
            ->assertJsonPath('data.data.0.level', 3)
            ->assertJsonPath('data.data.0.level_label', 'Independent')
            ->assertJsonPath('data.data.0.changed_on', '2026-10-01')
            ->assertJsonPath('data.data.0.reason', 'Led the migration')
            ->assertJsonPath('data.data.0.recorded_by_user_name', $this->admin->user_name)
            ->assertJsonPath('data.data.2.changed_on', '2026-08-01');
        $this->assertSame(SkillLevelEnum::INDEPENDENT, $skill->refresh()->current_level);
    }

    public function test_a_backdated_entry_does_not_replace_the_current_level(): void
    {
        $skill = Skill::factory()->atLevel(SkillLevelEnum::INDEPENDENT)->create();

        $this->call('POST', self::BASE.'/store', ['skill_id' => $skill->id, 'level' => 1, 'changed_on' => '2020-01-01'], $this->owner)->assertOk();

        $this->assertSame(SkillLevelEnum::INDEPENDENT, $skill->refresh()->current_level);
        $this->assertSame(2, $skill->levels()->count());
    }

    public function test_reaching_the_target_achieves_open_goals_only(): void
    {
        $skill = Skill::factory()->atLevel(SkillLevelEnum::WITH_HELP)->create();
        $reached = LearningGoal::factory()->create(['skill_id' => $skill->id, 'target_level' => SkillLevelEnum::INDEPENDENT]);
        $later = LearningGoal::factory()->create(['skill_id' => $skill->id, 'target_level' => SkillLevelEnum::CAN_TEACH]);
        $dropped = LearningGoal::factory()->create(['skill_id' => $skill->id, 'target_level' => SkillLevelEnum::INDEPENDENT, 'status' => GoalStatus::DROPPED]);

        // Dated today: the factory's first entry is dated today too, so an earlier date would be a backdated entry
        $this->call('POST', self::BASE.'/store', ['skill_id' => $skill->id, 'level' => 3], $this->owner)->assertOk();

        $reached->refresh();
        $this->assertSame(GoalStatus::ACHIEVED, $reached->status);
        $this->assertSame(now()->format('Y-m-d'), $reached->achieved_on?->format('Y-m-d'));
        $this->assertSame(GoalStatus::OPEN, $later->refresh()->status);
        $this->assertSame(GoalStatus::DROPPED, $dropped->refresh()->status);
        $this->assertSame(1, AuditLog::query()->where(['auditable_type' => 'learning_goal', 'auditable_id' => $reached->id, 'event' => 'updated'])->count());
    }

    public function test_history_cannot_be_edited_or_deleted(): void
    {
        $skill = Skill::factory()->create();
        $entryId = $skill->levels()->value('id');

        $this->call('PUT', self::BASE."/update/{$entryId}", ['level' => 4], $this->owner)->assertStatus(CommonVal::HTTP_NOT_FOUND);
        $this->call('POST', self::BASE.'/delete', ['ids' => [$entryId]], $this->owner)->assertStatus(CommonVal::HTTP_NOT_FOUND);
        $this->assertSame(SkillLevelEnum::LEARNING, $skill->levels()->firstOrFail()->level);
    }

    public function test_rejects_invalid_entries(): void
    {
        $skill = Skill::factory()->create();

        foreach ([
            'level' => ['skill_id' => $skill->id, 'level' => 5],
            'skill_id' => ['skill_id' => 999999, 'level' => 2],
            'changed_on' => ['skill_id' => $skill->id, 'level' => 2, 'changed_on' => now()->addDay()->format('Y-m-d')],
        ] as $field => $payload) {
            $this->call('POST', self::BASE.'/store', $payload, $this->owner)
                ->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT)
                ->assertJsonStructure(['error' => ['messages' => [$field]]]);
        }
        $this->call('GET', self::BASE.'/list', [], $this->owner)->assertStatus(CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        $this->assertSame(1, $skill->levels()->count());
    }

    public function test_viewer_cannot_record_a_level(): void
    {
        $skill = Skill::factory()->create();
        $viewer = $this->loginAs(AdminMst::factory()->create());

        $this->call('POST', self::BASE.'/store', ['skill_id' => $skill->id, 'level' => 2], $viewer)->assertStatus(CommonVal::HTTP_FORBIDDEN);
        $this->call('GET', self::BASE.'/list', ['skill_id' => $skill->id], $viewer)->assertOk();
    }
}
