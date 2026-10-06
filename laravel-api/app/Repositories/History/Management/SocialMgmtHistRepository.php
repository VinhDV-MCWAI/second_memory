<?php

declare(strict_types=1);

namespace App\Repositories\History\Management;

use App\Models\History\Management\SocialMgmtHist;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SocialMgmtHistRepository extends CrudRepository
{
    public function __construct(SocialMgmtHist $model)
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
                'social_mgmt_id',
                'name',
                'slug',
                'link',
                'image',
                'status',
                'is_display',
                'rank_order',
                'action',
                'author_id',
            ])
            ->with(['socialMgmt:id,name', 'author:id,user_name']);
        $this->applyFilters($query, $payload, ['social_mgmt_id', 'link', 'image', 'status', 'is_display', 'rank_order', 'action', 'author_id'], ['name', 'slug']);
        $this->applyDateRange($query, $payload);
        $this->applySorting($query, $payload);

        return $query->paginate($payload['per_page'] ?? 15, ['*'], 'page', $payload['page'] ?? 1);
    }
}
