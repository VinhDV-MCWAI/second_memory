<?php

declare(strict_types=1);

namespace App\Repositories\Ledger;

use App\Constants\LedgerConst;
use App\Models\Ledger\Skill;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class SkillRepository extends CrudRepository
{
    public function __construct(Skill $model)
    {
        parent::__construct($model);
    }

    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()->with('tags:id,name');

        $this->applyFilters($query, $payload, ['id', 'category', 'is_public', 'current_level']);
        if (isset($payload['name'])) {
            $query->whereRaw('lower(name) like lower(?)', ['%'.$payload['name'].'%']);
        }
        if (isset($payload['tag_id'])) {
            $query->whereHas('tags', fn ($tags) => $tags->where('tag.id', $payload['tag_id']));
        }

        $this->applySorting($query, $this->allowedSort($payload, ['id', 'name', 'category', 'current_level', 'updated_at']), 'name');

        return $query->paginate($payload['per_page'] ?? LedgerConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }

    public function executeStore(array $payload): int
    {
        $payload['slug'] = $this->uniqueSlug((string) $payload['name']);
        $id = parent::executeStore($payload);
        $this->syncTags($id, $payload);

        return $id;
    }

    public function executeUpdate(array $payload): int
    {
        $id = parent::executeUpdate($payload);
        $this->syncTags($id, $payload);

        return $id;
    }

    public function currentLevel(int $skillId): int
    {
        /** @var Skill $skill */
        $skill = $this->model->newQuery()->findOrFail($skillId, ['id', 'current_level']);

        return $skill->current_level->value;
    }

    public function setCurrentLevel(int $skillId, int $level): void
    {
        $this->model->newQuery()->whereKey($skillId)->update(['current_level' => $level]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncTags(int $skillId, array $payload): void
    {
        if (array_key_exists('tag_ids', $payload)) {
            /** @var Skill $skill */
            $skill = $this->model->newQuery()->findOrFail($skillId);
            $skill->tags()->sync($payload['tag_ids'] ?? []);
        }
    }

    /**
     * "Kỹ năng" → "ky-nang"; a clash gets a numeric suffix ("ky-nang-2").
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'skill';
        $slug = $base;
        for ($i = 2; $this->model->newQuery()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
