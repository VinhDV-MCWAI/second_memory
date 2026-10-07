<?php

declare(strict_types=1);

namespace App\Http\Controllers\History\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Management\SliderMgmtHist\DeleteSliderMgmtHistRequest;
use App\Http\Requests\History\Management\SliderMgmtHist\ListSliderMgmtHistRequest;
use App\Http\Requests\History\Management\SliderMgmtHist\StoreSliderMgmtHistRequest;
use App\Http\Requests\History\Management\SliderMgmtHist\UpdateSliderMgmtHistRequest;
use App\Http\Resources\History\Management\SliderMgmtHistResource;
use App\Services\History\Management\SliderMgmtHistService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SliderMgmtHistController extends Controller
{
    public function __construct(
        protected SliderMgmtHistService $sliderMgmtHist
    ) {}

    /**
     * SliderMgmtHist list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, SliderMgmtHistResource>>
     */
    public function list(ListSliderMgmtHistRequest $request): AnonymousResourceCollection
    {
        return $this->sliderMgmtHist->list($request->validated());
    }

    /**
     * Store slider mgmt hist
     */
    public function store(StoreSliderMgmtHistRequest $request): int
    {
        return $this->sliderMgmtHist->store($request->validated());
    }

    /**
     * Update slider mgmt hist
     */
    public function update(UpdateSliderMgmtHistRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->sliderMgmtHist->update($payload);
    }

    /**
     * Delete slider mgmt hist
     */
    public function delete(DeleteSliderMgmtHistRequest $request): void
    {
        $this->sliderMgmtHist->delete($request->validated());
    }
}
