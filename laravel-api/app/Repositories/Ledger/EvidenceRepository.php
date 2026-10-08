<?php

declare(strict_types=1);

namespace App\Repositories\Ledger;

use App\Constants\LedgerConst;
use App\Models\Ledger\Evidence;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EvidenceRepository extends CrudRepository
{
    public function __construct(Evidence $model)
    {
        parent::__construct($model);
    }

    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['skills:id,name', 'tags:id,name']);

        $this->applyFilters($query, $payload, ['id', 'type', 'source', 'is_public']);
        if (isset($payload['title'])) {
            $query->whereRaw('lower(title) like lower(?)', ['%'.$payload['title'].'%']);
        }
        if (isset($payload['skill_id'])) {
            $query->whereHas('skills', fn ($skills) => $skills->where('skill.id', $payload['skill_id']));
        }
        if (isset($payload['tag_id'])) {
            $query->whereHas('tags', fn ($tags) => $tags->where('tag.id', $payload['tag_id']));
        }

        $this->applySorting($query, $this->allowedSort($payload, ['id', 'title', 'type', 'occurred_on', 'updated_at']), 'occurred_on', 'desc');

        return $query->paginate($payload['per_page'] ?? LedgerConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }

    public function find(int $id): Evidence
    {
        /** @var Evidence */
        return $this->model->newQuery()->findOrFail($id);
    }

    public function executeStore(array $payload): int
    {
        $id = parent::executeStore($payload);
        $this->syncLinks($id, $payload);

        return $id;
    }

    public function executeUpdate(array $payload): int
    {
        $id = parent::executeUpdate($payload);
        $this->syncLinks($id, $payload);

        return $id;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncLinks(int $evidenceId, array $payload): void
    {
        $evidence = $this->find($evidenceId);
        if (array_key_exists('skill_ids', $payload)) {
            $evidence->skills()->sync($payload['skill_ids']);
        }
        if (array_key_exists('tag_ids', $payload)) {
            $evidence->tags()->sync($payload['tag_ids'] ?? []);
        }
    }
}
