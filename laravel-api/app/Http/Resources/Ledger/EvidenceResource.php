<?php

declare(strict_types=1);

namespace App\Http\Resources\Ledger;

use App\Constants\LedgerConst;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\Skill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin view of an evidence link (ADR-0008).
 *
 * @mixin Evidence
 */
class EvidenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'type' => $this->type,
            'title' => (string) $this->title,
            'url' => (string) $this->url,
            'occurred_on' => $this->occurred_on->format(LedgerConst::DATE_FORMAT),
            'summary' => $this->summary,
            'is_public' => (bool) $this->is_public,
            'source' => $this->source,
            'unpublished_at' => $this->unpublished_at?->toIso8601String(),
            'skills' => $this->whenLoaded('skills', fn () => $this->skills
                ->map(fn (Skill $skill): array => ['id' => (int) $skill->id, 'name' => (string) $skill->name])
                ->all()),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
