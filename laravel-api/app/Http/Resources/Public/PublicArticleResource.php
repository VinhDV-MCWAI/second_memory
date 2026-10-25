<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A public article as a documentation page (ADR-0022): the body as safe HTML with its table of
 * contents, its public categories, and its place in a book: the tree, the way up, the previous and
 * next pages; or, outside a book, articles sharing a category.
 *
 * @mixin Article
 *
 * @phpstan-type Node array{title: string, slug: string, children: list<mixed>}
 * @phpstan-type Place array{book: Node|null, ancestors: list<array{title: string, slug: string}>, previous: array{title: string, slug: string}|null, next: array{title: string, slug: string}|null, related: list<array{title: string, slug: string}>}
 */
class PublicArticleResource extends JsonResource
{
    /**
     * @param  Place  $place
     */
    public function __construct(Article $article, private readonly array $place)
    {
        parent::__construct($article);
    }

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
            'view_count' => (int) $this->view_count,
            /** End users who keep it in their favourites */
            'favorite_count' => (int) $this->getAttribute('favorite_count'),
            'created_at' => (string) $this->created_at?->toIso8601String(),
            'updated_at' => (string) $this->updated_at?->toIso8601String(),
            /** @var list<array{name: string, slug: string}> */
            'categories' => $this->categories->map(fn (ContentCategory $category): array => [
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
            ])->all(),
            /**
             * The book this page is in, from its top: `{title, slug, children}` nested; null outside a book
             *
             * @var array{title: string, slug: string, children: list<array{title: string, slug: string, children: list<mixed>}>}|null
             */
            'book' => $this->place['book'],
            /** @var list<array{title: string, slug: string}> */
            'ancestors' => $this->place['ancestors'],
            /** @var array{title: string, slug: string}|null */
            'previous' => $this->place['previous'],
            /** @var array{title: string, slug: string}|null */
            'next' => $this->place['next'],
            /** @var list<array{title: string, slug: string}> */
            'related' => $this->place['related'],
        ];
    }
}
