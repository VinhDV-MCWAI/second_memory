<?php

declare(strict_types=1);

namespace App\Repositories\Management;

use App\Constants\CommonVal;
use App\Enums\IsDelete;
use App\Interfaces\Management\UserMgmtInterface;
use App\Models\Management\UserMgmt;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class UserMgmtRepository extends BaseRepository implements UserMgmtInterface
{
    public function __construct(UserMgmt $model)
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
                'email',
                'user_name',
                'first_name',
                'last_name',
                'address',
                'phone_number',
                'birth',
                'gender',
                'status',
                'is_active',
                'avatar',
                'updated_at',
            ])
            ->notDeleted();

        // Apply filters
        $this->applyFilters($query, $payload, [
            'id',
            'email',
            'phone_number',
            'birth',
            'gender',
            'status',
            'is_active',
            'avatar',
        ], [
            'user_name',
            'first_name',
            'last_name',
            'address',
        ]);

        // Apply date range
        $this->applyDateRange($query, $payload);

        // Apply sorting
        $this->applySorting($query, $payload);

        // Pagination
        $perPage = $payload['per_page'] ?? 15;
        $page = $payload['page'] ?? 1;

        // dump($query->toSql(), $query->getBindings());

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Create new record
     */
    public function executeStore(array $payload): int
    {
        // Format birth date if provided
        if (isset($payload['birth']) && ! empty($payload['birth'])) {
            $payload['birth'] = Carbon::createFromFormat(CommonVal::DATE_FORMAT, $payload['birth'])->format('Y-m-d');
        }

        $model = $this->model->newInstance()->fill(
            Arr::only($payload, $this->model->getFillable())
        );

        // Hash password if provided
        if (isset($payload['password']) && ! empty($payload['password'])) {
            $model->password = Hash::make($payload['password']);
        }

        $model->save();

        return $model->id;
    }

    /**
     * Update record
     */
    public function executeUpdate(array $payload): int
    {
        $model = $this->model->findOrFail($payload['id']);

        if ($model->isDeleted()) {
            throw new \LogicException('Cannot update deleted record');
        }

        // Format birth date if provided
        if (isset($payload['birth']) && ! empty($payload['birth'])) {
            $payload['birth'] = Carbon::createFromFormat(CommonVal::DATE_FORMAT, $payload['birth'])->format('Y-m-d');
        }

        $model->fill(Arr::only($payload, $this->model->getFillable()));

        // Hash password if provided
        if (isset($payload['password']) && ! empty($payload['password'])) {
            $model->password = Hash::make($payload['password']);
        }

        $model->save();

        return $model->id;
    }

    /**
     * Delete record (soft delete)
     */
    public function executeDelete(array $ids): void
    {
        // Soft delete
        $this->model->whereIn('id', $ids)
            ->notDeleted()
            ->update(['is_delete' => IsDelete::TRUE->value]);
    }
}
