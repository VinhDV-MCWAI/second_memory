<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A public article (ADR-0022): the body as safe HTML with its table of contents, and its public categories.
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
        $rendered = app(MarkdownRenderer::class)->render((string) $this->body, MarkdownRenderer::MODE_PUBLIC, $this->id);

        return [
            'title' => (string) $this->title,
            'slug' => (string) $this->slug,
            'key' => (string) $this->public_key,
            'summary' => $this->summary,
            /** Embedded articles and files are in it; headings carry ids */
            'body_html' => $rendered['html'],
            /**
             * Headings of level 2 and 3, for "On this page"
             *
             * @var list<array{level: int, id: string, text: string}>
             */
            'toc' => $rendered['toc'],
            'updated_at' => (string) $this->updated_at?->toIso8601String(),
            /** @var list<array{name: string, slug: string}> */
            'categories' => $this->categories->map(fn (ContentCategory $category): array => [
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
            ])->all(),
        ];
    }
}
