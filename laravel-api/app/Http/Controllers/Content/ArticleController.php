<?php

declare(strict_types=1);

namespace App\Http\Controllers\Content;

use App\Enums\MoveDirection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\Article\DeleteArticleRequest;
use App\Http\Requests\Content\Article\ListArticleRequest;
use App\Http\Requests\Content\Article\PreviewArticleRequest;
use App\Http\Requests\Content\Article\StoreArticleRequest;
use App\Http\Requests\Content\Article\UpdateArticleRequest;
use App\Http\Requests\Content\MoveRequest;
use App\Http\Resources\Content\ArticleResource;
use App\Services\Content\ArticleService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Admin side of the public site's articles (ADR-0021).
 */
class ArticleController extends Controller
{
    public function __construct(private readonly ArticleService $articles) {}

    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, ArticleResource>>
     */
    public function list(ListArticleRequest $request): AnonymousResourceCollection
    {
        return $this->articles->list($request->validated());
    }

    public function store(StoreArticleRequest $request): int
    {
        return $this->articles->store($request->validated());
    }

    public function update(UpdateArticleRequest $request, string $id): int
    {
        return $this->articles->update([...$request->validated(), 'id' => $id]);
    }

    public function delete(DeleteArticleRequest $request): void
    {
        $this->articles->delete($request->validated());
    }

    /**
     * One step up or down among its siblings (the same parent)
     */
    public function move(MoveRequest $request, string $id): void
    {
        $this->articles->move((int) $id, MoveDirection::from((string) $request->validated('direction')));
    }

    /**
     * Markdown rendered as the public page shows it: HTML with embedded articles and files (signed
     * links) and the table of contents. Nothing is saved
     *
     * @return array{html: string, toc: list<array{level: int, id: string, text: string}>}
     */
    public function preview(PreviewArticleRequest $request): array
    {
        return $this->articles->preview((string) $request->validated('body'));
    }
}
