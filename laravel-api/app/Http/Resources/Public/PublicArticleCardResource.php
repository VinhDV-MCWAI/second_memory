<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Models\Content\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An article in a public list (a category page, the tabs of the home page; ADR-0022, ADR-0024),
 * with the numbers its order is chosen by. No ids.
 *
 * @mixin Article
 */
class PublicArticleCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => (string) $this->title,
            'slug' => (string) $this->slug,
            'summary' => $this->summary,
            'view_count' => (int) $this->view_count,
            /** End users who keep it in either list */
            'save_count' => (int) $this->getAttribute('save_count'),
            /** End users who keep it in their favourites */
            'favorite_count' => (int) $this->getAttribute('favorite_count'),
            'created_at' => (string) $this->created_at?->toIso8601String(),
            'updated_at' => (string) $this->updated_at?->toIso8601String(),
        ];
    }
}
