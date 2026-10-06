<?php

namespace App\Http\Controllers\History\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Management\SettingLinkMgmtHist\DeleteSettingLinkMgmtHistRequest;
use App\Http\Requests\History\Management\SettingLinkMgmtHist\ListSettingLinkMgmtHistRequest;
use App\Http\Requests\History\Management\SettingLinkMgmtHist\StoreSettingLinkMgmtHistRequest;
use App\Http\Requests\History\Management\SettingLinkMgmtHist\UpdateSettingLinkMgmtHistRequest;
use App\Services\History\Management\SettingLinkMgmtHistService;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingLinkMgmtHistController extends Controller
{
    public function __construct(
        protected SettingLinkMgmtHistService $settingLinkMgmtHist
    ) {}

    /**
     * SettingLinkMgmtHist list
     */
    public function list(ListSettingLinkMgmtHistRequest $request): JsonResource
    {
        return $this->settingLinkMgmtHist->list($request->validated());
    }

    /**
     * Store setting link mgmt hist
     */
    public function store(StoreSettingLinkMgmtHistRequest $request): int
    {
        return $this->settingLinkMgmtHist->store($request->validated());
    }

    /**
     * Update setting link mgmt hist
     */
    public function update(UpdateSettingLinkMgmtHistRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->settingLinkMgmtHist->update($payload);
    }

    /**
     * Delete setting link mgmt hist
     */
    public function delete(DeleteSettingLinkMgmtHistRequest $request): void
    {
        $this->settingLinkMgmtHist->delete($request->validated());
    }
}
