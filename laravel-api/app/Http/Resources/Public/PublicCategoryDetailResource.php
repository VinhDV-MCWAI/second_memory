<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A public category (ADR-0022): the trail of categories above it, description as safe HTML,
 * evidence, the categories right inside it, and one page of its articles in the order asked for.
 *
 * @mixin ContentCategory
 */
class PublicCategoryDetailResource extends JsonResource
{
    /**
     * @param  LengthAwarePaginator<int, Article>  $articles
     * @param  list<array{name: string, slug: string}>  $ancestors  from the top down to the parent
     */
    public function __construct(ContentCategory $category, private readonly LengthAwarePaginator $articles, private readonly array $ancestors)
    {
        parent::__construct($category);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            /**
             * The categories above this one, from the top down to its parent; empty at the top
             *
             * @var list<array{name: string, slug: string}>
             */
            'ancestors' => $this->ancestors,
            'description_html' => app(MarkdownRenderer::class)->toHtml((string) $this->description),
            'attachments' => PublicAttachmentResource::collection($this->attachments),
            /** @var list<array{name: string, slug: string, article_count: int}> */
            'categories' => $this->children->map(fn (ContentCategory $category): array => [
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
                'article_count' => (int) $category->getAttribute('articles_count'),
            ])->all(),
            /** @var list<array{title: string, slug: string, summary: string|null, view_count: int, save_count: int, created_at: string, updated_at: string}> */
            'articles' => collect($this->articles->items())->map(fn (Article $article): array => [
                'title' => (string) $article->title,
                'slug' => (string) $article->slug,
                'summary' => $article->summary,
                'view_count' => (int) $article->view_count,
                'save_count' => (int) $article->getAttribute('save_count'),
                'created_at' => (string) $article->created_at?->toIso8601String(),
                'updated_at' => (string) $article->updated_at?->toIso8601String(),
            ])->all(),
            'page' => $this->articles->currentPage(),
            'last_page' => $this->articles->lastPage(),
            'total' => $this->articles->total(),
        ];
    }
}
