<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\SettingLinkMgmt\DeleteSettingLinkMgmtRequest;
use App\Http\Requests\Management\SettingLinkMgmt\ListSettingLinkMgmtRequest;
use App\Http\Requests\Management\SettingLinkMgmt\StoreSettingLinkMgmtRequest;
use App\Http\Requests\Management\SettingLinkMgmt\UpdateSettingLinkMgmtRequest;
use App\Http\Resources\Management\SettingLinkMgmtResource;
use App\Services\Management\SettingLinkMgmtService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SettingLinkMgmtController extends Controller
{
    public function __construct(
        protected SettingLinkMgmtService $settingLinkMgmt
    ) {}

    /**
     * SettingLinkMgmt list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, SettingLinkMgmtResource>>
     */
    public function list(ListSettingLinkMgmtRequest $request): AnonymousResourceCollection
    {
        return $this->settingLinkMgmt->list($request->validated());
    }

    /**
     * Store setting link mgmt
     */
    public function store(StoreSettingLinkMgmtRequest $request): int
    {
        return $this->settingLinkMgmt->store($request->validated());
    }

    /**
     * Update setting link mgmt
     */
    public function update(UpdateSettingLinkMgmtRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->settingLinkMgmt->update($payload);
    }

    /**
     * Delete setting link mgmt
     */
    public function delete(DeleteSettingLinkMgmtRequest $request): void
    {
        $this->settingLinkMgmt->delete($request->validated());
    }
}
