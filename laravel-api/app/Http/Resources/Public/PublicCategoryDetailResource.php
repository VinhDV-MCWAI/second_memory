<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A public category with its public articles, in the owner's order (ADR-0021).
 *
 * @mixin ContentCategory
 */
class PublicCategoryDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'description' => $this->description,
            /** @var list<array{title: string, slug: string, summary: string|null, updated_at: string}> */
            'articles' => $this->articles->map(fn (Article $article): array => [
                'title' => (string) $article->title,
                'slug' => (string) $article->slug,
                'summary' => $article->summary,
                'updated_at' => (string) $article->updated_at?->toIso8601String(),
            ])->all(),
        ];
    }
}
