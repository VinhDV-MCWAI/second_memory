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
use Spatie\Permission\Models\Role;

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
                'avatar',
                'updated_at',
            ])
            ->with(['roles:id,name', 'departments:id,code,name'])
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

        if (isset($payload['role_id'])) {
            $query->whereHas('roles', fn ($roles) => $roles->where('roles.id', $payload['role_id']));
        }

        if (isset($payload['department_id'])) {
            $query->whereHas('departments', fn ($departments) => $departments->where('department.id', $payload['department_id']));
        }

        // Apply date range filter
        $this->applyDateRange($query, $payload);

        // Apply sorting
        $this->applySorting($query, $payload, ['id', 'user_name', 'first_name', 'last_name', 'email', 'status', 'is_active', 'created_at', 'updated_at']);

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
            ->whereHas('roles', fn ($roles) => $roles->where('name', AdminRole::OWNER->value))
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
        /** @var AdminMst $model */
        $model = $this->model->newInstance()->fill(
            Arr::only($payload, $this->model->getFillable())
        );

        // Handle password hashing
        if (isset($payload['password']) && ! empty($payload['password'])) {
            $model->password = Hash::make($payload['password']);
        }

        $model->save();
        // A new admin without a choice gets the read-only role, as before ADR-0013
        $model->syncRoles(array_map('intval', $payload['role_ids'] ?? [$this->viewerRoleId()]));
        $model->departments()->sync(array_map('intval', $payload['department_ids'] ?? []));

        return $model->id;
    }

    /**
     * Update record
     */
    public function executeUpdate(array $payload): int
    {
        /** @var AdminMst $model */
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

        // Update using fill(); the password is only set below, hashed, when a new one is given
        $model->fill(Arr::except(Arr::only($payload, $this->model->getFillable()), ['password']));

        // Handle password hashing (only if password is provided)
        if (isset($payload['password']) && ! empty($payload['password'])) {
            $model->password = Hash::make($payload['password']);
        }

        $model->save();
        if (array_key_exists('role_ids', $payload)) {
            $model->syncRoles(array_map('intval', $payload['role_ids']));
        }
        if (array_key_exists('department_ids', $payload)) {
            $model->departments()->sync(array_map('intval', $payload['department_ids']));
        }

        return $model->id;
    }

    /**
     * Whether the given roles include the owner role.
     *
     * @param  array<int, int|string>  $roleIds
     */
    public function includesOwnerRole(array $roleIds): bool
    {
        return Role::query()->whereIn('id', $roleIds)->where('name', AdminRole::OWNER->value)->exists();
    }

    private function viewerRoleId(): int
    {
        return (int) Role::findByName(AdminRole::VIEWER->value, CommonVal::ADMIN_GUARD)->getKey();
    }
}
