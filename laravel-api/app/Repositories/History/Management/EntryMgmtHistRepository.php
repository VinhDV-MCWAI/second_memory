<?php

declare(strict_types=1);

namespace App\Repositories\History\Management;

use App\Models\History\Management\EntryMgmtHist;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EntryMgmtHistRepository extends CrudRepository
{
    public function __construct(EntryMgmtHist $model)
    {
        parent::__construct($model);
    }

    /**
     * Get list with pagination
     */
    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select([
                'id',
                'entry_mgmt_id',
                'name',
                'slug',
                'status',
                'is_display',
                'rank_order',
                'action',
                'author_id',
            ])
            ->with(['entryMgmt:id,name', 'author:id,user_name']);

        // Apply filters
        $this->applyFilters($query, $payload, [
            'entry_mgmt_id',
            'status',
            'is_display',
            'rank_order',
            'action',
            'author_id',
        ], [
            'name',
            'slug',
        ]);

        // Apply date range
        $this->applyDateRange($query, $payload);

        // Apply sorting
        $this->applySorting($query, $payload);

        // Pagination
        $perPage = $payload['per_page'] ?? 15;
        $page = $payload['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
