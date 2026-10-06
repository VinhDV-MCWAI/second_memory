<?php

namespace App\Http\Controllers\History\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Master\FeatureMstHist\DeleteFeatureMstHistRequest;
use App\Http\Requests\History\Master\FeatureMstHist\ListFeatureMstHistRequest;
use App\Http\Requests\History\Master\FeatureMstHist\StoreFeatureMstHistRequest;
use App\Http\Requests\History\Master\FeatureMstHist\UpdateFeatureMstHistRequest;
use App\Services\History\Master\FeatureMstHistService;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureMstHistController extends Controller
{
    public function __construct(
        protected FeatureMstHistService $featureMstHist
    ) {}

    /**
     * FeatureMstHist list
     */
    public function list(ListFeatureMstHistRequest $request): JsonResource
    {
        return $this->featureMstHist->list($request->validated());
    }

    /**
     * Store feature mst hist
     */
    public function store(StoreFeatureMstHistRequest $request): int
    {
        return $this->featureMstHist->store($request->validated());
    }

    /**
     * Update feature mst hist
     */
    public function update(UpdateFeatureMstHistRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->featureMstHist->update($payload);
    }

    /**
     * Delete feature mst hist
     */
    public function delete(DeleteFeatureMstHistRequest $request): void
    {
        $this->featureMstHist->delete($request->validated());
    }
}
