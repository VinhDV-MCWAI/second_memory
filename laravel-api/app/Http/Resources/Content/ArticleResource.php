<?php

declare(strict_types=1);

namespace App\Http\Resources\Content;

use App\Models\Content\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An article as the admin screens edit it: the Markdown source, not HTML.
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
            'content_category_id' => (int) $this->content_category_id,
            'category_name' => (string) $this->category->name,
            'category_slug' => (string) $this->category->slug,
            'title' => (string) $this->title,
            'slug' => (string) $this->slug,
            'summary' => $this->summary,
            'body' => (string) $this->body,
            'sort_order' => (int) $this->sort_order,
            'is_public' => (bool) $this->is_public,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
