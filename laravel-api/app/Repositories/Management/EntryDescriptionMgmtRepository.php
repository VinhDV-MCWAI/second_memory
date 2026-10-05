<?php

declare(strict_types=1);

namespace App\Repositories\Management;

use App\Enums\IsDelete;
use App\Interfaces\Management\EntryDescriptionMgmtInterface;
use App\Models\Management\EntryDescriptionMgmt;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;


class EntryDescriptionMgmtRepository extends BaseRepository implements EntryDescriptionMgmtInterface
{
  public function __construct(EntryDescriptionMgmt $model)
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
        'title',
        'summary',
        'article',
        'status',
        'is_display',
        'rank_order',
        'updated_at',
      ])
      ->notDeleted();

    // Apply filters
    $this->applyFilters($query, $payload, [
      'id',
      'status',
      'is_display',
      'rank_order',
    ], [
      'title',
      'summary',
      'article',
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
    $model = $this->model->newInstance()->fill(
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

    if ($model->isDeleted()) {
      throw new \LogicException('Cannot update deleted record');
    }

    $model->fill(Arr::only($payload, $this->model->getFillable()));
    $model->save();

    return $model->id;
  }

  /**
   * Delete record (soft delete)
   *
   * @param array $ids
   * @return void
   */
  public function executeDelete(array $ids): void
  {
    // Soft delete
    $this->model->whereIn('id', $ids)
      ->notDeleted()
      ->update(['is_delete' => IsDelete::TRUE->value]);
  }

  /**
   * Search descriptions
   *
   * @param string $query
   * @return \Illuminate\Support\Collection
   */
  public function searchDescriptions(string $query): \Illuminate\Support\Collection
  {
    return $this->model->query()
      ->select([
        'id',
        'title',
        'summary',
        'article',
        'rank_order',
      ])
      ->where('is_display', true)
      ->where('status', 1)
      ->notDeleted()
      ->where(function ($q) use ($query) {
        $q->where('title', 'ILIKE', "%{$query}%")
          ->orWhere('summary', 'ILIKE', "%{$query}%")
          ->orWhere('article', 'ILIKE', "%{$query}%");
      })
      ->orderBy('rank_order', 'asc')
      ->limit(10)
      ->get();
  }
}
