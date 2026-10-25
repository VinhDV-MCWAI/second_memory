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
    public function __construct(
        ContentCategory $category,
        private readonly LengthAwarePaginator $articles,
        private readonly array $ancestors,
        private readonly int $favoriteCount,
    ) {
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
            'view_count' => (int) $this->view_count,
            /** End users who keep it as a favourite */
            'favorite_count' => $this->favoriteCount,
            'description_html' => app(MarkdownRenderer::class)->toHtml((string) $this->description),
            'attachments' => PublicAttachmentResource::collection($this->attachments),
            /** @var list<array{name: string, slug: string, article_count: int}> */
            'categories' => $this->children->map(fn (ContentCategory $category): array => [
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
                'article_count' => (int) $category->getAttribute('articles_count'),
            ])->all(),
            'articles' => PublicArticleCardResource::collection($this->articles->items()),
            'page' => $this->articles->currentPage(),
            'last_page' => $this->articles->lastPage(),
            'total' => $this->articles->total(),
        ];
    }
}
