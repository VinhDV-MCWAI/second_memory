<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Models\Ledger\Skill;
use App\Models\Ledger\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A public skill in the list (RFC-002 §4.3). Separate from the admin resource, so no private
 * field can reach the public API by accident.
 *
 * @mixin Skill
 */
class PublicSkillResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'category' => (string) $this->category,
            'current_level' => $this->current_level->value,
            'current_level_label' => $this->current_level->label(),
            'tags' => $this->tags->map(fn (Tag $tag): string => (string) $tag->name)->all(),
        ];
    }
}
