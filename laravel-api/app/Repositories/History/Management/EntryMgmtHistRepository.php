<?php

declare(strict_types=1);

namespace App\Repositories\History\Management;

use App\Interfaces\History\Management\EntryMgmtHistInterface;
use App\Models\History\Management\EntryMgmtHist;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;


class EntryMgmtHistRepository extends BaseRepository implements EntryMgmtHistInterface
{
  public function __construct(EntryMgmtHist $model)
  {
    parent::__construct($model);
  }

  /**
   * Get list with pagination
   *
   * @param array $payload
   * @return LengthAwarePaginator
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

  /**
   * Create new record
   *
   * @param array $payload
   * @return int
   */
  public function executeStore(array $payload): int
  {
    $model = $this->model->fill(
      Arr::only($payload, $this->model->getFillable())
    );

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
    $this->model->whereIn('id', $ids)->delete();
  }
}
