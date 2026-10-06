<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\FeatureMst\DeleteFeatureMstRequest;
use App\Http\Requests\Master\FeatureMst\ListFeatureMstRequest;
use App\Http\Requests\Master\FeatureMst\StoreFeatureMstRequest;
use App\Http\Requests\Master\FeatureMst\UpdateFeatureMstRequest;
use App\Services\Master\FeatureMstService;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureMstController extends Controller
{
    public function __construct(
        protected FeatureMstService $featureMst
    ) {}

    /**
     * FeatureMst list
     */
    public function list(ListFeatureMstRequest $request): JsonResource
    {
        return $this->featureMst->list($request->validated());
    }

    /**
     * Store feature mst
     */
    public function store(StoreFeatureMstRequest $request): int
    {
        return $this->featureMst->store($request->validated());
    }

    /**
     * Update feature mst
     */
    public function update(UpdateFeatureMstRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->featureMst->update($payload);
    }

    /**
     * Delete feature mst
     */
    public function delete(DeleteFeatureMstRequest $request): void
    {
        $this->featureMst->delete($request->validated());
    }
}
