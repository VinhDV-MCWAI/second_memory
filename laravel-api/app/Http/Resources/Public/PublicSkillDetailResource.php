<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Constants\LedgerConst;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\Skill;
use App\Models\Ledger\SkillLevel;
use Illuminate\Http\Request;

/**
 * One public skill with the dates of its level changes (no reasons, no recorder) and its
 * public evidence (REQ-002 US-3). Goals are never public.
 *
 * @mixin Skill
 */
class PublicSkillDetailResource extends PublicSkillResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'description' => $this->description,
            'history' => $this->levels->map(fn (SkillLevel $entry): array => [
                'level' => $entry->level->value,
                'level_label' => $entry->level->label(),
                'changed_on' => $entry->changed_on->format(LedgerConst::DATE_FORMAT),
            ])->all(),
            'evidence' => $this->evidence->map(fn (Evidence $evidence): array => [
                'type' => $evidence->type,
                'title' => $evidence->title,
                'url' => $evidence->url,
                'occurred_on' => $evidence->occurred_on->format(LedgerConst::DATE_FORMAT),
                'summary' => $evidence->summary,
            ])->all(),
        ];
    }
}
