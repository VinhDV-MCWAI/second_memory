<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Constants\CommonVal;
use App\Constants\PermissionConst;
use App\Models\Master\AdminMst;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles of the dynamic permissions (ADR-0013), with their permission names and how many admins hold them.
 */
class RoleRepository extends CrudRepository
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select(['id', 'name'])
            ->where('guard_name', CommonVal::ADMIN_GUARD)
            ->with('permissions:id,name')
            ->selectSub($this->adminCount(), 'admin_count');

        $this->applyFilters($query, $payload, ['id']);
        if (isset($payload['name'])) {
            $query->whereRaw('lower(name) like lower(?)', ['%'.$payload['name'].'%']);
        }

        $this->applySorting($query, $payload, ['id', 'name'], 'name');

        return $query->paginate($payload['per_page'] ?? PermissionConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }

    public function executeStore(array $payload): int
    {
        $id = parent::executeStore($payload);
        $this->syncPermissions($id, $payload);

        return $id;
    }

    public function executeUpdate(array $payload): int
    {
        $id = parent::executeUpdate($payload);
        $this->syncPermissions($id, $payload);

        return $id;
    }

    /**
     * One by one, so the package clears its permission cache.
     */
    public function executeDelete(array $ids): void
    {
        $this->model->newQuery()->whereIn('id', $ids)->get()->each->delete();
    }

    public function nameOf(int $id): string
    {
        return (string) $this->model->newQuery()->whereKey($id)->value('name');
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return list<string>
     */
    public function namesOf(array $ids): array
    {
        return $this->model->newQuery()->whereIn('id', $ids)->pluck('name')->all();
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function anyAssigned(array $ids): bool
    {
        return DB::table('model_has_roles')
            ->whereIn('role_id', $ids)
            ->where('model_type', (new AdminMst)->getMorphClass())
            ->exists();
    }

    /**
     * The package's Role guards nothing, so name the columns; the guard is always `admin`
     * (inside a request the package would default to `sanctum`).
     */
    protected function fillable(array $payload): array
    {
        return [...Arr::only($payload, ['name']), 'guard_name' => CommonVal::ADMIN_GUARD];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncPermissions(int $id, array $payload): void
    {
        if (array_key_exists('permissions', $payload)) {
            /** @var Role $role */
            $role = $this->model->newQuery()->findOrFail($id);
            $role->syncPermissions($payload['permissions'] ?? []);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function adminCount(): Builder
    {
        return DB::table('model_has_roles')
            ->selectRaw('count(*)')
            ->whereColumn('model_has_roles.role_id', 'roles.id')
            ->where('model_type', (new AdminMst)->getMorphClass());
    }
}
