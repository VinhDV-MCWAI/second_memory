<?php

declare(strict_types=1);

namespace App\Repositories\History\Management;

use App\Interfaces\History\Management\EntryDescriptionMgmtHistInterface;
use App\Models\History\Management\EntryDescriptionMgmtHist;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class EntryDescriptionMgmtHistRepository extends BaseRepository implements EntryDescriptionMgmtHistInterface
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

    /**
     * Create new record
     */
    public function executeStore(array $payload): int
    {
        $model = $this->model->newInstance()->fill(Arr::only($payload, $this->model->getFillable()));
        $model->save();

        return $model->id;
    }

    /**
     * Update record
     */
    public function executeUpdate(array $payload): int
    {
        $model = $this->model->findOrFail($payload['id']);
        $model->fill(Arr::only($payload, $this->model->getFillable()));
        $model->save();

        return $model->id;
    }

    /**
     * Delete record (hard delete)
     */
    public function executeDelete(array $ids): void
    {
        $this->model->whereIn('id', $ids)->delete();
    }
}
