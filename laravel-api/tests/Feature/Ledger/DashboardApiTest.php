<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Constants\CommonVal;
use App\Enums\GoalStatus;
use App\Enums\SkillLevel as SkillLevelEnum;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\LearningGoal;
use App\Models\Ledger\Skill;
use App\Models\Master\AdminMst;
use Tests\Concerns\AuthenticatesAdmins;
use Tests\TestCase;

/**
 * Dashboard numbers (REQ-002 US-7).
 */
final class DashboardApiTest extends TestCase
{
    use AuthenticatesAdmins;

    private const URL = '/api/admin/dashboard/summary';

    public function test_counts_skills_evidence_goals_and_levels(): void
    {
        $postgres = Skill::factory()->public()->atLevel(SkillLevelEnum::INDEPENDENT)->create();
        Skill::factory()->atLevel(SkillLevelEnum::INDEPENDENT)->create();
        Skill::factory()->create();
        Evidence::factory()->public()->create();
        Evidence::factory()->create();
        Evidence::factory()->public()->fromObsidian()->create(['unpublished_at' => now()]);
        LearningGoal::factory()->for($postgres)->create(['target_level' => SkillLevelEnum::CAN_TEACH]);
        LearningGoal::factory()->for($postgres)->create(['status' => GoalStatus::DROPPED]);

        $this->call('GET', self::URL, [], $this->loginAs(AdminMst::factory()->create()))
            ->assertOk()
            ->assertJsonPath('data', [
                'skills' => 3,
                'public_skills' => 1,
                'evidence' => 3,
                'public_evidence' => 1,
                'open_goals' => 1,
                'levels' => [
                    ['level' => 1, 'level_label' => 'Learning', 'count' => 1],
                    ['level' => 2, 'level_label' => 'Can use with help', 'count' => 0],
                    ['level' => 3, 'level_label' => 'Independent', 'count' => 2],
                    ['level' => 4, 'level_label' => 'Can teach others', 'count' => 0],
                ],
            ]);
    }

    public function test_requires_a_session(): void
    {
        $this->getJson(self::URL)->assertStatus(CommonVal::HTTP_UNAUTHORIZED);
    }
}
