<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\SocialMgmt\DeleteSocialMgmtRequest;
use App\Http\Requests\Management\SocialMgmt\ListSocialMgmtRequest;
use App\Http\Requests\Management\SocialMgmt\StoreSocialMgmtRequest;
use App\Http\Requests\Management\SocialMgmt\UpdateSocialMgmtRequest;
use App\Services\Management\SocialMgmtService;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialMgmtController extends Controller
{
    public function __construct(
        protected SocialMgmtService $socialMgmt
    ) {}

    /**
     * SocialMgmt list
     */
    public function list(ListSocialMgmtRequest $request): JsonResource
    {
        return $this->socialMgmt->list($request->validated());
    }

    /**
     * Store social mgmt
     */
    public function store(StoreSocialMgmtRequest $request): int
    {
        return $this->socialMgmt->store($request->validated());
    }

    /**
     * Update social mgmt
     */
    public function update(UpdateSocialMgmtRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->socialMgmt->update($payload);
    }

    /**
     * Delete social mgmt
     */
    public function delete(DeleteSocialMgmtRequest $request): void
    {
        $this->socialMgmt->delete($request->validated());
    }
}
