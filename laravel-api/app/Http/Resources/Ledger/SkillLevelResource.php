<?php

declare(strict_types=1);

namespace App\Http\Resources\Ledger;

use App\Constants\LedgerConst;
use App\Models\Ledger\SkillLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SkillLevel
 */
class SkillLevelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'skill_id' => (int) $this->skill_id,
            'level' => $this->level->value,
            'level_label' => $this->level->label(),
            'reason' => $this->reason,
            'changed_on' => $this->changed_on->format(LedgerConst::DATE_FORMAT),
            'recorded_by_user_name' => $this->recorder?->user_name,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
