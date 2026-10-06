<?php

declare(strict_types=1);

namespace App\Repositories\History\Management;

use App\Models\History\Management\SliderMgmtHist;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SliderMgmtHistRepository extends CrudRepository
{
    public function __construct(SliderMgmtHist $model)
    {
        parent::__construct($model);
    }

    /**
     * Get list
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
            ->with(['sliderMgmt:id,title', 'author:id,user_name']);

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
}
