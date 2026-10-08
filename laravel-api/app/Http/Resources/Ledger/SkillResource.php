<?php

declare(strict_types=1);

namespace App\Http\Resources\Ledger;

use App\Models\Ledger\Skill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin view of a skill (ADR-0008). The public API has its own resource.
 *
 * @mixin Skill
 */
class SkillResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'category' => (string) $this->category,
            'description' => $this->description,
            'is_public' => (bool) $this->is_public,
            'current_level' => $this->current_level->value,
            'current_level_label' => $this->current_level->label(),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
