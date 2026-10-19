<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use App\Enums\SavedKind;
use App\Models\Ledger\Skill;
use App\Models\User\EndUserSaved;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One saved skill, evidence or article (ADR-0020, ADR-0021), with what the public site needs to
 * link to it: `key` is the skill slug or the public key; `skill_slug` the skill page that shows a
 * skill or an evidence; `category_slug` + `article_slug` the page of an article.
 *
 * @mixin EndUserSaved
 */
class SavedItemResource extends JsonResource
{
    public const TARGET_SKILL = 'skill';

    public const TARGET_EVIDENCE = 'evidence';

    public const TARGET_ARTICLE = 'article';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SavedKind $kind */
        $kind = $this->kind;
        $skill = $this->skill;
        $evidence = $this->evidence;
        $article = $this->article;
        /** @var Skill|null $page skill whose public page lists the item */
        $page = $skill ?? $evidence?->skills->first();

        return [
            'kind' => $kind->value,
            /** @var 'skill'|'evidence'|'article' */
            'target' => match (true) {
                $skill !== null => self::TARGET_SKILL,
                $article !== null => self::TARGET_ARTICLE,
                default => self::TARGET_EVIDENCE,
            },
            'key' => $skill !== null ? (string) $skill->slug : (string) ($article->public_key ?? $evidence?->public_key),
            'title' => (string) ($skill->name ?? $article->title ?? $evidence?->title),
            /** @var string|null */
            'skill_slug' => $page?->slug,
            /** @var string|null */
            'skill_name' => $page?->name,
            /** @var string|null */
            'category_slug' => $article?->category->slug,
            /** @var string|null */
            'category_name' => $article?->category->name,
            /** @var string|null */
            'article_slug' => $article?->slug,
            'saved_at' => $this->created_at->toIso8601String(),
        ];
    }
}
