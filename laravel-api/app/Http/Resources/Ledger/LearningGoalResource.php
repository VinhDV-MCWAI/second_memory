<?php

declare(strict_types=1);

namespace App\Http\Resources\Ledger;

use App\Constants\LedgerConst;
use App\Models\Ledger\LearningGoal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin view of a learning goal (ADR-0008). Goals are never public.
 *
 * @mixin LearningGoal
 */
class LearningGoalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'skill' => [
                'id' => (int) $this->skill->id,
                'name' => (string) $this->skill->name,
                'current_level' => $this->skill->current_level->value,
            ],
            'target_level' => $this->target_level,
            'target_level_label' => $this->target_level->label(),
            'target_date' => $this->target_date?->format(LedgerConst::DATE_FORMAT),
            'status' => $this->status,
            'achieved_on' => $this->achieved_on?->format(LedgerConst::DATE_FORMAT),
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
