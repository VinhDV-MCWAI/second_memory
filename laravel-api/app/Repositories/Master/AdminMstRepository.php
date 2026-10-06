<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Constants\CommonVal;
use App\Enums\IsDelete;
use App\Interfaces\Master\AdminMstInterface;
use App\Models\Master\AdminMst;
use App\Repositories\BaseRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class AdminMstRepository extends BaseRepository implements AdminMstInterface
{
    public function __construct(AdminMst $model)
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
            ->with(['roles:id,name,permission', 'departments:id,code,name']) // Eager load relationships
            ->notDeleted(); // Use scope from HasSoftDelete trait

        // Apply exact match filters
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
            // Apply LIKE filters
            'user_name',
            'first_name',
            'last_name',
            'address',
        ]);

        // Apply date range filter
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
     */
    public function executeStore(array $payload): int
    {
        if (isset($payload['birth']) && ! empty($payload['birth'])) {
            try {
                $payload['birth'] = Carbon::createFromFormat(CommonVal::DATE_FORMAT, $payload['birth'])->format('Y-m-d');
            } catch (\Exception $e) {
                // Keep original value if parsing fails
            }
        }

        // Use fill() with only fillable fields
        $model = $this->model->newInstance()->fill(
            Arr::only($payload, $this->model->getFillable())
        );

        // Handle password hashing
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

        // Check not soft deleted
        if ($model->isDeleted()) {
            throw new \LogicException('Cannot update deleted record');
        }

        if (isset($payload['birth']) && ! empty($payload['birth'])) {
            try {
                $payload['birth'] = Carbon::createFromFormat(CommonVal::DATE_FORMAT, $payload['birth'])->format('Y-m-d');
            } catch (\Exception $e) {
                // Keep original value if parsing fails, let database handle it or fail
            }
        }

        // Update using fill()
        $model->fill(Arr::only($payload, $this->model->getFillable()));

        // Handle password hashing (only if password is provided)
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
        // Soft delete using scope
        $this->model->whereIn('id', $ids)
            ->notDeleted()
            ->update(['is_delete' => IsDelete::TRUE->value]);
    }
}
