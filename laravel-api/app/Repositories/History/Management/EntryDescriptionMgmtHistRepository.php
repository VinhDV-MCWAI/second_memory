<?php

declare(strict_types=1);

namespace App\Repositories\History\Management;

use App\Models\History\Management\EntryDescriptionMgmtHist;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EntryDescriptionMgmtHistRepository extends CrudRepository
{
    public function __construct(EntryDescriptionMgmtHist $model)
    {
        parent::__construct($model);
    }

    /**
     * Get list with pagination
     */
    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select(['id', 'entry_description_mgmt_id', 'title', 'summary', 'article', 'status', 'is_display', 'rank_order', 'action', 'author_id'])
            ->with(['entryDescriptionMgmt:id,title', 'author:id,user_name']);
        $this->applyFilters($query, $payload, ['entry_description_mgmt_id', 'status', 'is_display', 'rank_order', 'action', 'author_id'], ['title', 'summary', 'article']);
        $this->applyDateRange($query, $payload);
        $this->applySorting($query, $payload);

        return $query->paginate($payload['per_page'] ?? 15, ['*'], 'page', $payload['page'] ?? 1);
    }
}
