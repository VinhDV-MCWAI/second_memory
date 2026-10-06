<?php

declare(strict_types=1);

namespace App\Http\Controllers\History\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Management\BannerMgmtHist\DeleteBannerMgmtHistRequest;
use App\Http\Requests\History\Management\BannerMgmtHist\ListBannerMgmtHistRequest;
use App\Http\Requests\History\Management\BannerMgmtHist\StoreBannerMgmtHistRequest;
use App\Http\Requests\History\Management\BannerMgmtHist\UpdateBannerMgmtHistRequest;
use App\Services\History\Management\BannerMgmtHistService;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerMgmtHistController extends Controller
{
    public function __construct(
        protected BannerMgmtHistService $bannerMgmtHist
    ) {}

    /**
     * BannerMgmtHist list
     */
    public function list(ListBannerMgmtHistRequest $request): JsonResource
    {
        return $this->bannerMgmtHist->list($request->validated());
    }

    /**
     * Store banner mgmt hist
     */
    public function store(StoreBannerMgmtHistRequest $request): int
    {
        return $this->bannerMgmtHist->store($request->validated());
    }

    /**
     * Update banner mgmt hist
     */
    public function update(UpdateBannerMgmtHistRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->bannerMgmtHist->update($payload);
    }

    /**
     * Delete banner mgmt hist
     */
    public function delete(DeleteBannerMgmtHistRequest $request): void
    {
        $this->bannerMgmtHist->delete($request->validated());
    }
}
