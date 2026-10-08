<?php

declare(strict_types=1);

namespace App\Repositories\Ledger;

use App\Constants\LedgerConst;
use App\Models\Ledger\Tag;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TagRepository extends CrudRepository
{
    public function __construct(Tag $model)
    {
        parent::__construct($model);
    }

    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()->select(['id', 'name']);

        $this->applyFilters($query, $payload, ['id']);
        if (isset($payload['name'])) {
            $query->whereRaw('lower(name) like lower(?)', ['%'.$payload['name'].'%']);
        }

        $this->applySorting($query, $this->allowedSort($payload, ['id', 'name']), 'name');

        return $query->paginate($payload['per_page'] ?? LedgerConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }
}
