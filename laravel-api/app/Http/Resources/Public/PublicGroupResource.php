<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A group of the home page with its categories, like the boards of a forum (ADR-0022). No ids.
 *
 * @mixin ContentCategory
 */
class PublicGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            /** @var list<array{name: string, slug: string, article_count: int, latest: array{title: string, slug: string, created_at: string}|null}> */
            'categories' => $this->children->map(function (ContentCategory $category): array {
                /** @var Article|null $latest */
                $latest = $category->articles->first();

                return [
                    'name' => (string) $category->name,
                    'slug' => (string) $category->slug,
                    'article_count' => (int) $category->getAttribute('articles_count'),
                    'latest' => $latest === null ? null : [
                        'title' => $latest->title,
                        'slug' => $latest->slug,
                        'created_at' => (string) $latest->created_at?->toIso8601String(),
                    ],
                ];
            })->all(),
        ];
    }
}
