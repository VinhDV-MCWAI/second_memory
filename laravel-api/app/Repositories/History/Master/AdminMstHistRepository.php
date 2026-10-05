<?php

declare(strict_types=1);

namespace App\Repositories\History\Master;

use App\Interfaces\History\Master\AdminMstHistInterface;
use App\Models\History\Master\AdminMstHist;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class AdminMstHistRepository extends BaseRepository implements AdminMstHistInterface
{
    public function __construct(AdminMstHist $model)
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
            ->select(['id','admin_mst_id','email','user_name','first_name','last_name','address','phone_number','birth','gender','status','is_active','avatar','action','author_id'])
            ->with(['adminMst:id,email,user_name', 'author:id,user_name']);
        $this->applyFilters($query, $payload, ['admin_mst_id','email','phone_number','birth','gender','status','is_active','avatar','action','author_id'], ['user_name','first_name','last_name','address']);
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
