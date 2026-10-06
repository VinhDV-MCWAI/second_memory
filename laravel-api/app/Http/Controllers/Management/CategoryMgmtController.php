<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\CategoryMgmt\DeleteCategoryMgmtRequest;
use App\Http\Requests\Management\CategoryMgmt\ListCategoryMgmtRequest;
use App\Http\Requests\Management\CategoryMgmt\StoreCategoryMgmtRequest;
use App\Http\Requests\Management\CategoryMgmt\UpdateCategoryMgmtRequest;
use App\Http\Resources\Management\CategoryMgmtResource;
use App\Services\Management\CategoryMgmtService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryMgmtController extends Controller
{
    public function __construct(
        protected CategoryMgmtService $categoryMgmt
    ) {}

    /**
     * CategoryMgmt list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, CategoryMgmtResource>>
     */
    public function list(ListCategoryMgmtRequest $request): AnonymousResourceCollection
    {
        return $this->categoryMgmt->list($request->validated());
    }

    /**
     * Store category mgmt
     */
    public function store(StoreCategoryMgmtRequest $request): int
    {
        return $this->categoryMgmt->store($request->validated());
    }

    /**
     * Update category mgmt
     */
    public function update(UpdateCategoryMgmtRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->categoryMgmt->update($payload);
    }

    /**
     * Delete category mgmt
     */
    public function delete(DeleteCategoryMgmtRequest $request): void
    {
        $this->categoryMgmt->delete($request->validated());
    }
}
