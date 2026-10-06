<?php

namespace App\Services\Master;

use App\Enums\ActionType;
use App\Http\Resources\Master\FeatureMstResource;
use App\Interfaces\History\Master\FeatureMstHistInterface;
use App\Interfaces\Master\FeatureMstInterface;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureMstService extends BaseService
{
    public function __construct(
        protected FeatureMstInterface $featureMst,
        protected FeatureMstHistInterface $featureMstHist
    ) {}

    protected function getHistoryRepository()
    {
        return $this->featureMstHist;
    }

    protected function getHistoryForeignKey(): string
    {
        return 'feature_mst_id';
    }

    /**
     * Get feature mst list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->featureMst->list($payload);

        return FeatureMstResource::collection($list);
    }

    /**
     * Store feature mst
     */
    public function store(array $payload): int
    {
        $id = $this->featureMst->executeStore($payload);
        $this->recordHistory($id, ActionType::CREATE, $payload);

        return $id;
    }

    /**
     * Update feature mst
     */
    public function update(array $payload): int
    {
        $id = $payload['id'];
        $affected = $this->featureMst->executeUpdate($payload);
        $this->recordHistory($id, ActionType::UPDATE, $payload);

        return $affected;
    }

    /**
     * Delete feature mst
     */
    public function delete(array $payload): void
    {
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            $this->featureMst->executeDelete($payload['ids'] ?? []);

            return;
        }

        foreach ($payload['ids'] as $id) {
            $this->recordHistory($id, ActionType::DELETE, $payload);
        }

        $this->featureMst->executeDelete($payload['ids']);
    }
}
