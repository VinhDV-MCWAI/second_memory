<?php

declare(strict_types=1);

namespace App\Http\Controllers\History\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Management\UserMgmtHist\DeleteUserMgmtHistRequest;
use App\Http\Requests\History\Management\UserMgmtHist\ListUserMgmtHistRequest;
use App\Http\Requests\History\Management\UserMgmtHist\StoreUserMgmtHistRequest;
use App\Http\Requests\History\Management\UserMgmtHist\UpdateUserMgmtHistRequest;
use App\Http\Resources\History\Management\UserMgmtHistResource;
use App\Services\History\Management\UserMgmtHistService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserMgmtHistController extends Controller
{
    public function __construct(
        protected UserMgmtHistService $userMgmtHist
    ) {}

    /**
     * UserMgmtHist list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, UserMgmtHistResource>>
     */
    public function list(ListUserMgmtHistRequest $request): AnonymousResourceCollection
    {
        return $this->userMgmtHist->list($request->validated());
    }

    /**
     * Store user mgmt hist
     */
    public function store(StoreUserMgmtHistRequest $request): int
    {
        return $this->userMgmtHist->store($request->validated());
    }

    /**
     * Update user mgmt hist
     */
    public function update(UpdateUserMgmtHistRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->userMgmtHist->update($payload);
    }

    /**
     * Delete user mgmt hist
     */
    public function delete(DeleteUserMgmtHistRequest $request): void
    {
        $this->userMgmtHist->delete($request->validated());
    }
}
