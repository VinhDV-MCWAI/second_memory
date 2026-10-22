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
 * A public group or category (ADR-0022): description as safe HTML, evidence, the categories of a
 * group, and one page of its articles in the order asked for.
 *
 * @mixin ContentCategory
 */
class PublicCategoryDetailResource extends JsonResource
{
    /**
     * @param  LengthAwarePaginator<int, Article>  $articles
     */
    public function __construct(ContentCategory $category, private readonly LengthAwarePaginator $articles)
    {
        parent::__construct($category);
    }

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
            /** @var list<array{name: string, slug: string, article_count: int}> */
            'categories' => $this->children->map(fn (ContentCategory $category): array => [
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
                'article_count' => (int) $category->getAttribute('articles_count'),
            ])->all(),
            /** @var list<array{title: string, slug: string, summary: string|null, created_at: string, updated_at: string}> */
            'articles' => collect($this->articles->items())->map(fn (Article $article): array => [
                'title' => (string) $article->title,
                'slug' => (string) $article->slug,
                'summary' => $article->summary,
                'created_at' => (string) $article->created_at?->toIso8601String(),
                'updated_at' => (string) $article->updated_at?->toIso8601String(),
            ])->all(),
            'page' => $this->articles->currentPage(),
            'last_page' => $this->articles->lastPage(),
            'total' => $this->articles->total(),
        ];
    }
}
