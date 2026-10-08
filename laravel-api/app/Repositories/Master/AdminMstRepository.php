<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Constants\CommonVal;
use App\Enums\AdminRole;
use App\Models\Master\AdminMst;
use App\Repositories\SoftDeleteCrudRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class AdminMstRepository extends SoftDeleteCrudRepository
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
                'role',
                'avatar',
                'updated_at',
            ])
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
            'role',
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
        $this->applySorting($query, $payload, ['id', 'user_name', 'first_name', 'last_name', 'email', 'role', 'status', 'is_active', 'created_at', 'updated_at']);

        // Pagination
        $perPage = $payload['per_page'] ?? 15;
        $page = $payload['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Active, not deleted owners, leaving out the given admin IDs.
     *
     * @param  array<int, int|string>  $exceptIds
     */
    public function countActiveOwnersExcept(array $exceptIds): int
    {
        return $this->model->query()
            ->where('is_delete', false)
            ->where('is_active', true)
            ->where('role', AdminRole::OWNER)
            ->whereNotIn('id', $exceptIds)
            ->count();
    }

    /**
     * Create new record
     */
    public function executeStore(array $payload): int
    {
        if (isset($payload['birth']) && ! empty($payload['birth'])) {
            try {
                $payload['birth'] = Carbon::createFromFormat(CommonVal::DATE_FORMAT, $payload['birth'])->format('Y-m-d');
            } catch (\Exception) {
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
            } catch (\Exception) {
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
}
