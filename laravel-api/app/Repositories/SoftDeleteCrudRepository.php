<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\IsDelete;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * CRUD repository for tables with an `is_delete` flag (model uses HasSoftDelete).
 */
abstract class SoftDeleteCrudRepository extends CrudRepository
{
    /**
     * Relations that must be empty before a row may be deleted.
     *
     * @var list<string>
     */
    protected array $deleteBlockedBy = [];

    public function executeDelete(array $ids): void
    {
        if ($this->deleteBlockedBy !== []) {
            $this->checkCanDelete($ids, $this->deleteBlockedBy);
        }

        $this->model->whereIn('id', $ids)
            ->notDeleted()
            ->update(['is_delete' => IsDelete::TRUE->value]);
    }

    protected function findForUpdate(int|string $id): Model
    {
        $model = parent::findForUpdate($id);

        if ($model->isDeleted()) {
            throw new LogicException('Cannot update deleted record');
        }

        return $model;
    }
}
