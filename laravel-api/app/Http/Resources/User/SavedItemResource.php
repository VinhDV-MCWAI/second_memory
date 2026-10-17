<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use App\Enums\SavedKind;
use App\Models\Ledger\Skill;
use App\Models\User\EndUserSaved;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One saved skill or evidence (ADR-0020), with what the public site needs to link to it:
 * `key` is the skill slug or the evidence public key, `skill_slug` the page that shows it.
 *
 * @mixin EndUserSaved
 */
class SavedItemResource extends JsonResource
{
    public const TARGET_SKILL = 'skill';

    public const TARGET_EVIDENCE = 'evidence';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SavedKind $kind */
        $kind = $this->kind;
        $skill = $this->skill;
        $evidence = $this->evidence;
        /** @var Skill|null $page skill whose public page lists the evidence */
        $page = $evidence?->skills->first();

        return [
            'kind' => $kind->value,
            /** @var 'skill'|'evidence' */
            'target' => $skill !== null ? self::TARGET_SKILL : self::TARGET_EVIDENCE,
            'key' => $skill !== null ? (string) $skill->slug : (string) $evidence?->public_key,
            'title' => $skill !== null ? (string) $skill->name : (string) $evidence?->title,
            'skill_slug' => $skill !== null ? (string) $skill->slug : (string) $page?->slug,
            'skill_name' => $skill !== null ? (string) $skill->name : (string) $page?->name,
            'saved_at' => $this->created_at->toIso8601String(),
        ];
    }
}
