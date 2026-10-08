<?php

declare(strict_types=1);

namespace Database\Factories\Ledger;

use App\Enums\GoalStatus;
use App\Enums\SkillLevel as SkillLevelEnum;
use App\Models\Ledger\LearningGoal;
use App\Models\Ledger\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningGoal>
 */
class LearningGoalFactory extends Factory
{
    protected $model = LearningGoal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'skill_id' => Skill::factory(),
            'target_level' => SkillLevelEnum::INDEPENDENT,
            'target_date' => now()->addMonths(3)->toDateString(),
            'status' => GoalStatus::OPEN,
            'achieved_on' => null,
            'note' => null,
        ];
    }
}
