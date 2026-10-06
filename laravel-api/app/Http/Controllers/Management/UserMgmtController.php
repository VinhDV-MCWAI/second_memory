<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\UserMgmt\DeleteUserMgmtRequest;
use App\Http\Requests\Management\UserMgmt\ListUserMgmtRequest;
use App\Http\Requests\Management\UserMgmt\StoreUserMgmtRequest;
use App\Http\Requests\Management\UserMgmt\UpdateUserMgmtRequest;
use App\Services\Management\UserMgmtService;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMgmtController extends Controller
{
    public function __construct(
        protected UserMgmtService $userMgmt
    ) {}

    /**
     * UserMgmt list
     */
    public function list(ListUserMgmtRequest $request): JsonResource
    {
        // dump($request->validated());
        return $this->userMgmt->list($request->validated());
    }

    /**
     * Store user mgmt
     */
    public function store(StoreUserMgmtRequest $request): int
    {
        return $this->userMgmt->store($request->validated());
    }

    /**
     * Update user mgmt
     */
    public function update(UpdateUserMgmtRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->userMgmt->update($payload);
    }

    /**
     * Delete user mgmt
     */
    public function delete(DeleteUserMgmtRequest $request): void
    {
        $this->userMgmt->delete($request->validated());
    }
}
