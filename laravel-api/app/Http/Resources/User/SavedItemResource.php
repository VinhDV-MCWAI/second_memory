<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use App\Enums\SavedKind;
use App\Enums\SavedTarget;
use App\Models\User\EndUserSaved;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One saved article or category (ADR-0020, ADR-0022, ADR-0024), with what the public site needs to
 * link to it. `key` names the target the way the save request does: an article's public key, a
 * category's slug. A read-later article carries the heading the reader reached.
 *
 * @mixin EndUserSaved
 */
class SavedItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SavedKind $kind */
        $kind = $this->kind;
        $article = $this->article;
        $category = $this->category;

        return [
            'kind' => $kind->value,
            'type' => ($article === null ? SavedTarget::CATEGORY : SavedTarget::ARTICLE)->value,
            'key' => (string) ($article->public_key ?? $category?->slug),
            'title' => (string) ($article->title ?? $category?->name),
            /** Page of the article; null for a category */
            'article_slug' => $article?->slug,
            /** Page of the category; null for an article */
            'category_slug' => $category?->slug,
            /** Id of the heading reached in a read-later article; null = from the top */
            'anchor' => $this->reading_anchor,
            'read_at' => $this->read_at?->toIso8601String(),
            'saved_at' => $this->created_at->toIso8601String(),
        ];
    }
}
