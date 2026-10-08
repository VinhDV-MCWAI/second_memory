<?php

declare(strict_types=1);

namespace App\Models\Ledger;

use App\Enums\SkillLevel as SkillLevelEnum;
use App\Models\Master\AdminMst;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry of a skill's level history. Rows are only ever inserted (REQ-002 US-1).
 *
 * @property int $skill_id
 * @property SkillLevelEnum $level
 */
class SkillLevel extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'skill_level';

    protected $fillable = [
        'skill_id',
        'level',
        'reason',
        'changed_on',
        'admin_mst_id',
    ];

    /**
     * @return BelongsTo<Skill, $this>
     */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    /**
     * @return BelongsTo<AdminMst, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(AdminMst::class, 'admin_mst_id');
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'skill_id' => 'integer',
            'level' => SkillLevelEnum::class,
            'changed_on' => 'date',
            'admin_mst_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
