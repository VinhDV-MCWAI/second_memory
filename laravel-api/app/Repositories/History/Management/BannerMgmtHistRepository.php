<?php

declare(strict_types=1);

namespace App\Repositories\History\Management;

use App\Models\History\Management\BannerMgmtHist;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BannerMgmtHistRepository extends CrudRepository
{
    public function __construct(BannerMgmtHist $model)
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
                'banner_mgmt_id',
                'title',
                'slug',
                'description',
                'link',
                'image',
                'position',
                'status',
                'action',
                'author_id',
            ])
            ->with(['bannerMgmt:id,title', 'author:id,user_name']);

        $this->applyFilters($query, $payload, [
            'banner_mgmt_id',
            'link',
            'image',
            'status',
            'action',
            'author_id',
        ], [
            'title',
            'slug',
            'description',
            'position',
        ]);

        $this->applyDateRange($query, $payload);
        $this->applySorting($query, $payload);

        $perPage = $payload['per_page'] ?? 15;
        $page = $payload['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
