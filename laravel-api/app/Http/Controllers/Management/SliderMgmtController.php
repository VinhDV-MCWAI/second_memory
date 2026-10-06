<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\SliderMgmt\DeleteSliderMgmtRequest;
use App\Http\Requests\Management\SliderMgmt\ListSliderMgmtRequest;
use App\Http\Requests\Management\SliderMgmt\StoreSliderMgmtRequest;
use App\Http\Requests\Management\SliderMgmt\UpdateSliderMgmtRequest;
use App\Http\Resources\Management\SliderMgmtResource;
use App\Services\Management\SliderMgmtService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SliderMgmtController extends Controller
{
    public function __construct(
        protected SliderMgmtService $sliderMgmt
    ) {}

    /**
     * SliderMgmt list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, SliderMgmtResource>>
     */
    public function list(ListSliderMgmtRequest $request): AnonymousResourceCollection
    {
        return $this->sliderMgmt->list($request->validated());
    }

    /**
     * Store slider mgmt
     */
    public function store(StoreSliderMgmtRequest $request): int
    {
        return $this->sliderMgmt->store($request->validated());
    }

    /**
     * Update slider mgmt
     */
    public function update(UpdateSliderMgmtRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->sliderMgmt->update($payload);
    }

    /**
     * Delete slider mgmt
     */
    public function delete(DeleteSliderMgmtRequest $request): void
    {
        $this->sliderMgmt->delete($request->validated());
    }
}
