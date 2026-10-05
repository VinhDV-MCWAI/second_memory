<?php

declare(strict_types=1);

namespace App\Repositories\History\Master;

use App\Interfaces\History\Master\FeatureMstHistInterface;
use App\Models\History\Master\FeatureMstHist;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;


class FeatureMstHistRepository extends BaseRepository implements FeatureMstHistInterface
{
  public function __construct(FeatureMstHist $model)
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
      ->select(['id', 'feature_mst_id', 'name', 'group_name', 'description', 'status', 'action', 'author_id'])
      ->with(['featureMst:id,name,group_name', 'author:id,user_name']);
    $this->applyFilters($query, $payload, ['feature_mst_id', 'status', 'action', 'author_id'], ['name', 'group_name', 'description']);
    $this->applyDateRange($query, $payload);
    $this->applySorting($query, $payload);
    return $query->paginate($payload['per_page'] ?? 15, ['*'], 'page', $payload['page'] ?? 1);
  }

  /**
   * Create new record
   *
   * @param array $payload
   * @return int
   */
  public function executeStore(array $payload): int
  {
    $model = $this->model->newInstance()->fill(Arr::only($payload, $this->model->getFillable()));
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
