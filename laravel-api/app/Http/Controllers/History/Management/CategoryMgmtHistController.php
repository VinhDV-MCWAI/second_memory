<?php

declare(strict_types=1);

namespace App\Http\Controllers\History\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Management\CategoryMgmtHist\DeleteCategoryMgmtHistRequest;
use App\Http\Requests\History\Management\CategoryMgmtHist\ListCategoryMgmtHistRequest;
use App\Http\Requests\History\Management\CategoryMgmtHist\StoreCategoryMgmtHistRequest;
use App\Http\Requests\History\Management\CategoryMgmtHist\UpdateCategoryMgmtHistRequest;
use App\Http\Resources\History\Management\CategoryMgmtHistResource;
use App\Services\History\Management\CategoryMgmtHistService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryMgmtHistController extends Controller
{
    public function __construct(
        protected CategoryMgmtHistService $categoryMgmtHist
    ) {}

    /**
     * CategoryMgmtHist list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, CategoryMgmtHistResource>>
     */
    public function list(ListCategoryMgmtHistRequest $request): AnonymousResourceCollection
    {
        return $this->categoryMgmtHist->list($request->validated());
    }

    /**
     * Store category mgmt hist
     */
    public function store(StoreCategoryMgmtHistRequest $request): int
    {
        return $this->categoryMgmtHist->store($request->validated());
    }

    /**
     * Update category mgmt hist
     */
    public function update(UpdateCategoryMgmtHistRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->categoryMgmtHist->update($payload);
    }

    /**
     * Delete category mgmt hist
     */
    public function delete(DeleteCategoryMgmtHistRequest $request): void
    {
        $this->categoryMgmtHist->delete($request->validated());
    }
}
