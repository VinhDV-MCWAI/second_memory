<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use App\Enums\SavedKind;
use App\Models\User\EndUserSaved;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One saved article (ADR-0020, ADR-0022), with what the public site needs to link to it: `key`
 * is the article's public key, `article_slug` names its page.
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

        return [
            'kind' => $kind->value,
            'key' => (string) $article->public_key,
            'title' => (string) $article->title,
            'article_slug' => (string) $article->slug,
            'saved_at' => $this->created_at->toIso8601String(),
        ];
    }
}
