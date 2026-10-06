<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\BannerMgmt\DeleteBannerMgmtRequest;
use App\Http\Requests\Management\BannerMgmt\ListBannerMgmtRequest;
use App\Http\Requests\Management\BannerMgmt\StoreBannerMgmtRequest;
use App\Http\Requests\Management\BannerMgmt\UpdateBannerMgmtRequest;
use App\Services\Management\BannerMgmtService;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerMgmtController extends Controller
{
    public function __construct(
        protected BannerMgmtService $bannerMgmt
    ) {}

    /**
     * BannerMgmt list
     */
    public function list(ListBannerMgmtRequest $request): JsonResource
    {
        return $this->bannerMgmt->list($request->validated());
    }

    /**
     * Store banner mgmt
     */
    public function store(StoreBannerMgmtRequest $request): int
    {
        return $this->bannerMgmt->store($request->validated());
    }

    /**
     * Update banner mgmt
     */
    public function update(UpdateBannerMgmtRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->bannerMgmt->update($payload);
    }

    /**
     * Delete banner mgmt
     */
    public function delete(DeleteBannerMgmtRequest $request): void
    {
        $this->bannerMgmt->delete($request->validated());
    }
}
