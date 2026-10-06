<?php

namespace App\Services\Management;

use App\Enums\ActionType;
use App\Http\Resources\Management\SliderMgmtResource;
use App\Repositories\History\Management\SliderMgmtHistRepository;
use App\Repositories\Management\SliderMgmtRepository;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\JsonResource;

class SliderMgmtService extends BaseService
{
    public function __construct(
        protected SliderMgmtRepository $sliderMgmt,
        protected SliderMgmtHistRepository $sliderMgmtHist
    ) {}

    protected function getHistoryRepository()
    {
        return $this->sliderMgmtHist;
    }

    protected function getHistoryForeignKey(): string
    {
        return 'slider_mgmt_id';
    }

    /**
     * Get slider mgmt list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->sliderMgmt->list($payload);

        return SliderMgmtResource::collection($list);
    }

    /**
     * Store slider mgmt
     */
    public function store(array $payload): int
    {
        $id = $this->sliderMgmt->executeStore($payload);
        $this->recordHistory($id, ActionType::CREATE, $payload);

        return $id;
    }

    /**
     * Update slider mgmt
     */
    public function update(array $payload): int
    {
        $id = $payload['id'];
        $affected = $this->sliderMgmt->executeUpdate($payload);
        $this->recordHistory($id, ActionType::UPDATE, $payload);

        return $affected;
    }

    /**
     * Delete slider mgmt
     */
    public function delete(array $payload): void
    {
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            $this->sliderMgmt->executeDelete($payload['ids'] ?? []);

            return;
        }

        foreach ($payload['ids'] as $id) {
            $this->recordHistory($id, ActionType::DELETE, $payload);
        }

        $this->sliderMgmt->executeDelete($payload['ids']);
    }
}
