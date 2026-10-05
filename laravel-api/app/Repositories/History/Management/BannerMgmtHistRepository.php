<?php

declare(strict_types=1);

namespace App\Repositories\History\Management;

use App\Enums\IsDelete;
use App\Interfaces\History\Management\BannerMgmtHistInterface;
use App\Models\History\Management\BannerMgmtHist;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;


class BannerMgmtHistRepository extends BaseRepository implements BannerMgmtHistInterface
{
  public function __construct(BannerMgmtHist $model)
  {
    parent::__construct($model);
  }

  /**
   * Get list
   *
   * @param array $payload
   * @return LengthAwarePaginator
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

  /**
   * Create new record
   *
   * @param array $payload
   * @return int
   */
  public function executeStore(array $payload): int
  {
    $model = $this->model->newInstance();
    $model->fill(Arr::only($payload, $this->model->getFillable()));
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
