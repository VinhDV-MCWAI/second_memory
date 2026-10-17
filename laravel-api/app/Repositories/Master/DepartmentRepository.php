<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Constants\DepartmentConst;
use App\Models\Master\Department;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepartmentRepository extends CrudRepository
{
    public function __construct(Department $model)
    {
        parent::__construct($model);
    }

    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select(['id', 'code', 'name', 'description', 'is_active', 'updated_at'])
            ->withCount('admins');

        $this->applyFilters($query, $payload, ['id', 'is_active']);
        foreach (['code', 'name'] as $column) {
            if (isset($payload[$column])) {
                $query->whereRaw("lower({$column}) like lower(?)", ['%'.$payload[$column].'%']);
            }
        }

        $this->applySorting($query, $payload, ['id', 'code', 'name', 'is_active', 'updated_at'], 'code');

        return $query->paginate($payload['per_page'] ?? DepartmentConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }
}
