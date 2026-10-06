<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\SliderMgmt\DeleteSliderMgmtRequest;
use App\Http\Requests\Management\SliderMgmt\ListSliderMgmtRequest;
use App\Http\Requests\Management\SliderMgmt\StoreSliderMgmtRequest;
use App\Http\Requests\Management\SliderMgmt\UpdateSliderMgmtRequest;
use App\Services\Management\SliderMgmtService;
use Illuminate\Http\Resources\Json\JsonResource;

class SliderMgmtController extends Controller
{
    public function __construct(
        protected SliderMgmtService $sliderMgmt
    ) {}

    /**
     * SliderMgmt list
     */
    public function list(ListSliderMgmtRequest $request): JsonResource
    {
        return $this->sliderMgmt->list($request->all());
    }

    /**
     * Store slider mgmt
     */
    public function store(StoreSliderMgmtRequest $request): int
    {
        return $this->sliderMgmt->store($request->all());
    }

    /**
     * Update slider mgmt
     */
    public function update(UpdateSliderMgmtRequest $request, string $id): int
    {
        $payload = $request->all();
        $payload['id'] = $id;

        return $this->sliderMgmt->update($payload);
    }

    /**
     * Delete slider mgmt
     */
    public function delete(DeleteSliderMgmtRequest $request): void
    {
        $this->sliderMgmt->delete($request->all());
    }
}
