<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A public category with its description as safe HTML, its evidence and its public articles,
 * newest change first (ADR-0022).
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
        $parent = $this->parent;

        return [
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            /** @var array{name: string, slug: string}|null */
            'group' => $parent === null ? null : ['name' => (string) $parent->name, 'slug' => (string) $parent->slug],
            'description_html' => app(MarkdownRenderer::class)->toHtml((string) $this->description),
            'attachments' => PublicAttachmentResource::collection($this->attachments),
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
