<?php

declare(strict_types=1);

namespace App\Http\Controllers\Content;

use App\Enums\MoveDirection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\Category\DeleteContentCategoryRequest;
use App\Http\Requests\Content\Category\ListContentCategoryRequest;
use App\Http\Requests\Content\Category\StoreContentCategoryRequest;
use App\Http\Requests\Content\Category\UpdateContentCategoryRequest;
use App\Http\Requests\Content\MoveRequest;
use App\Http\Resources\Content\ContentCategoryResource;
use App\Services\Content\ContentCategoryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Admin side of the public site's categories (ADR-0021).
 */
class ContentCategoryController extends Controller
{
    public function __construct(private readonly ContentCategoryService $categories) {}

    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, ContentCategoryResource>>
     */
    public function list(ListContentCategoryRequest $request): AnonymousResourceCollection
    {
        return $this->categories->list($request->validated());
    }

    public function store(StoreContentCategoryRequest $request): int
    {
        return $this->categories->store($request->validated());
    }

    public function update(UpdateContentCategoryRequest $request, string $id): int
    {
        return $this->categories->update([...$request->validated(), 'id' => $id]);
    }

    public function delete(DeleteContentCategoryRequest $request): void
    {
        $this->categories->delete($request->validated());
    }

    /**
     * One step up or down among its siblings (the same parent)
     */
    public function move(MoveRequest $request, string $id): void
    {
        $this->categories->move((int) $id, MoveDirection::from((string) $request->validated('direction')));
    }
}
