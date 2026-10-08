<?php

declare(strict_types=1);

namespace App\Models\Ledger;

use App\Enums\GoalStatus;
use App\Enums\SkillLevel as SkillLevelEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A private learning goal for one skill (REQ-002 US-6).
 *
 * @property SkillLevelEnum $target_level
 * @property GoalStatus $status
 */
class LearningGoal extends Model
{
    use HasFactory;

    protected $table = 'learning_goal';

    protected $fillable = [
        'skill_id',
        'target_level',
        'target_date',
        'status',
        'achieved_on',
        'note',
    ];

    /**
     * @return BelongsTo<Skill, $this>
     */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'skill_id' => 'integer',
            'target_level' => SkillLevelEnum::class,
            'target_date' => 'date',
            'status' => GoalStatus::class,
            'achieved_on' => 'date',
        ];
    }
}
