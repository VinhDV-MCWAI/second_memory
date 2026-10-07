<?php

declare(strict_types=1);

namespace App\Http\Controllers\History\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Management\SocialMgmtHist\DeleteSocialMgmtHistRequest;
use App\Http\Requests\History\Management\SocialMgmtHist\ListSocialMgmtHistRequest;
use App\Http\Requests\History\Management\SocialMgmtHist\StoreSocialMgmtHistRequest;
use App\Http\Requests\History\Management\SocialMgmtHist\UpdateSocialMgmtHistRequest;
use App\Http\Resources\History\Management\SocialMgmtHistResource;
use App\Services\History\Management\SocialMgmtHistService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SocialMgmtHistController extends Controller
{
    public function __construct(
        protected SocialMgmtHistService $socialMgmtHist
    ) {}

    /**
     * SocialMgmtHist list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, SocialMgmtHistResource>>
     */
    public function list(ListSocialMgmtHistRequest $request): AnonymousResourceCollection
    {
        return $this->socialMgmtHist->list($request->validated());
    }

    /**
     * Store social mgmt hist
     */
    public function store(StoreSocialMgmtHistRequest $request): int
    {
        return $this->socialMgmtHist->store($request->validated());
    }

    /**
     * Update social mgmt hist
     */
    public function update(UpdateSocialMgmtHistRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->socialMgmtHist->update($payload);
    }

    /**
     * Delete social mgmt hist
     */
    public function delete(DeleteSocialMgmtHistRequest $request): void
    {
        $this->socialMgmtHist->delete($request->validated());
    }
}
