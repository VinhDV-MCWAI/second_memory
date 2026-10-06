<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\SocialMgmt\DeleteSocialMgmtRequest;
use App\Http\Requests\Management\SocialMgmt\ListSocialMgmtRequest;
use App\Http\Requests\Management\SocialMgmt\StoreSocialMgmtRequest;
use App\Http\Requests\Management\SocialMgmt\UpdateSocialMgmtRequest;
use App\Http\Resources\Management\SocialMgmtResource;
use App\Services\Management\SocialMgmtService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SocialMgmtController extends Controller
{
    public function __construct(
        protected SocialMgmtService $socialMgmt
    ) {}

    /**
     * SocialMgmt list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, SocialMgmtResource>>
     */
    public function list(ListSocialMgmtRequest $request): AnonymousResourceCollection
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
