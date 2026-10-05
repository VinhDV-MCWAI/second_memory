<?php

declare(strict_types=1);

namespace App\Repositories\History\Management;

use App\Interfaces\History\Management\UserMgmtHistInterface;
use App\Models\History\Management\UserMgmtHist;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class UserMgmtHistRepository extends BaseRepository implements UserMgmtHistInterface
{
  public function __construct(UserMgmtHist $model)
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
      ->select(['id', 'user_mgmt_id', 'email', 'user_name', 'first_name', 'last_name', 'address', 'phone_number', 'birth', 'gender', 'status', 'is_active', 'avatar', 'action', 'author_id'])
      ->with(['userMgmt:id,email,user_name', 'author:id,user_name']);
    $this->applyFilters($query, $payload, ['user_mgmt_id', 'email', 'phone_number', 'birth', 'gender', 'status', 'is_active', 'avatar', 'action', 'author_id'], ['user_name', 'first_name', 'last_name', 'address']);
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
    if (isset($payload['password']) && $payload['password']) {
      $payload['password'] = Hash::make($payload['password']);
    }
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
    if (isset($payload['password']) && $payload['password']) {
      $payload['password'] = Hash::make($payload['password']);
    }
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
