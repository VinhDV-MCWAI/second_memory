<?php

declare(strict_types=1);

namespace App\Repositories\History\Management;

use App\Models\History\Management\UserMgmtHist;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserMgmtHistRepository extends CrudRepository
{
    public function __construct(UserMgmtHist $model)
    {
        parent::__construct($model);
    }

    /**
     * Get list with pagination
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

    protected function fillable(array $payload): array
    {
        if (! empty($payload['password'])) {
            $payload['password'] = Hash::make($payload['password']);
        }

        return parent::fillable($payload);
    }
}
