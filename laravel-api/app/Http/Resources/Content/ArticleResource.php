<?php

declare(strict_types=1);

namespace App\Http\Resources\Content;

use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An article as the admin screens edit it: the Markdown source, not HTML (ADR-0022).
 *
 * @mixin Article
 */
class ArticleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'parent_id' => $this->parent_id,
            /** @var string|null */
            'parent_title' => $this->parent?->title,
            /** @var list<array{id: int, name: string, slug: string}> */
            'categories' => $this->categories->map(fn (ContentCategory $category): array => [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
            ])->all(),
            'title' => (string) $this->title,
            'slug' => (string) $this->slug,
            'summary' => $this->summary,
            'body' => (string) $this->body,
            /** Place among the sub-pages of its parent, 1 first; changed with `move` */
            'sort_order' => (int) $this->sort_order,
            'is_public' => (bool) $this->is_public,
            'children_count' => (int) $this->children_count,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
