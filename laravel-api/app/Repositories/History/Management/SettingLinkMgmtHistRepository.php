<?php

declare(strict_types=1);

namespace App\Repositories\History\Management;

use App\Models\History\Management\SettingLinkMgmtHist;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SettingLinkMgmtHistRepository extends CrudRepository
{
    public function __construct(SettingLinkMgmtHist $model)
    {
        parent::__construct($model);
    }

    /**
     * Get list with pagination
     */
    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select(['id', 'setting_link_mgmt_id', 'key', 'value', 'action', 'author_id'])
            ->with(['settingLinkMgmt:id,key', 'author:id,user_name']);
        $this->applyFilters($query, $payload, ['setting_link_mgmt_id', 'action', 'author_id'], ['key', 'value']);
        $this->applyDateRange($query, $payload);
        $this->applySorting($query, $payload);

        return $query->paginate($payload['per_page'] ?? 15, ['*'], 'page', $payload['page'] ?? 1);
    }
}
