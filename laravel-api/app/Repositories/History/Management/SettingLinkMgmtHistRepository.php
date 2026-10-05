<?php

declare(strict_types=1);

namespace App\Repositories\History\Management;

use App\Enums\IsDelete;
use App\Interfaces\History\Management\SettingLinkMgmtHistInterface;
use App\Models\History\Management\SettingLinkMgmtHist;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;


class SettingLinkMgmtHistRepository extends BaseRepository implements SettingLinkMgmtHistInterface
{
  public function __construct(SettingLinkMgmtHist $model)
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
      ->select(['id', 'setting_link_mgmt_id', 'key', 'value', 'action', 'author_id'])
      ->with(['settingLinkMgmt:id,key', 'author:id,user_name']);
    $this->applyFilters($query, $payload, ['setting_link_mgmt_id', 'action', 'author_id'], ['key', 'value']);
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
   * Delete record (hard delete)
   *
   * @param array $ids
   * @return void
   */
  public function executeDelete(array $ids): void
  {
    $this->model->whereIn('id', $ids)->delete();
  }
}
