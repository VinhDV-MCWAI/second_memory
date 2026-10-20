<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A public article (ADR-0022): the body as safe HTML and its public categories.
 *
 * @mixin Article
 */
class PublicArticleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => (string) $this->title,
            'slug' => (string) $this->slug,
            'key' => (string) $this->public_key,
            'summary' => $this->summary,
            'body_html' => app(MarkdownRenderer::class)->toHtml((string) $this->body),
            'updated_at' => (string) $this->updated_at?->toIso8601String(),
            /** @var list<array{name: string, slug: string}> */
            'categories' => $this->categories->map(fn (ContentCategory $category): array => [
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
            ])->all(),
        ];
    }
}
