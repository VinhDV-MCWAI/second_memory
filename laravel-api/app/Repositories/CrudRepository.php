<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Repository for a table managed through list / store / update / delete endpoints.
 * Rows are removed for good; see SoftDeleteCrudRepository for `is_delete` tables.
 */
abstract class CrudRepository extends BaseRepository
{
    abstract public function list(array $payload): LengthAwarePaginator|Collection;

    public function executeStore(array $payload): int
    {
        $model = $this->model->newInstance()->fill($this->fillable($payload));
        $model->save();

        return (int) $model->getKey();
    }

    public function executeUpdate(array $payload): int
    {
        $model = $this->findForUpdate($payload['id']);
        $model->fill($this->fillable($payload));
        $model->save();

        return (int) $model->getKey();
    }

    public function executeDelete(array $ids): void
    {
        $this->model->whereIn('id', $ids)->delete();
    }

    protected function findForUpdate(int|string $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Payload reduced to the model's fillable columns. Override to normalize values before saving.
     */
    protected function fillable(array $payload): array
    {
        return Arr::only($payload, $this->model->getFillable());
    }
}
