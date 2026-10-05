<?php

declare(strict_types=1);

namespace App\Repositories\History\Management;

use App\Enums\IsDelete;
use App\Interfaces\History\Management\SliderMgmtHistInterface;
use App\Models\History\Management\SliderMgmtHist;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;


class SliderMgmtHistRepository extends BaseRepository implements SliderMgmtHistInterface
{
    public function __construct(SliderMgmtHist $model)
    {
        parent::__construct($model);
    }

    /**
     * Get list
     *
     * @param array $payload
     * @return LengthAwarePaginator
     */
    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select([
                'id',
                'slider_mgmt_id',
                'title',
                'slug',
                'link',
                'image',
                'status',
                'action',
                'author_id',
            ])
            ->with(['sliderMgmt:id,title', 'author:id,username']);

        $this->applyFilters($query, $payload, [
            'slider_mgmt_id',
            'link',
            'image',
            'status',
            'action',
            'author_id',
        ], [
            'title',
            'slug',
        ]);

        $this->applyDateRange($query, $payload);
        $this->applySorting($query, $payload);

        $perPage = $payload['per_page'] ?? 15;
        $page = $payload['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Create new record
     *
     * @param array $payload
     * @return int
     */
    public function executeStore(array $payload): int
    {
        $model = $this->model->fill(Arr::only($payload, $this->model->getFillable()));
        $model->save();
        return $model->id;
    }


    /**
     * Update record
     *
     * @param array $payload
     * @return int
     */
    public function executeUpdate(array $payload): int
    {
        $model = $this->model->findOrFail($payload['id']);
        $model->fill(Arr::only($payload, $this->model->getFillable()));
        $model->save();
        return $model->id;
    }

    /**
     * Delete record
     *
     * @param array $ids
     * @return void
     */
    public function executeDelete(array $ids): void
    {
        $this->model->whereIn('id', $ids)->update(['is_delete' => IsDelete::TRUE->value]);
    }

}
