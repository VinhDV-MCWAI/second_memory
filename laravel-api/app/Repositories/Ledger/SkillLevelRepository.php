<?php

declare(strict_types=1);

namespace App\Repositories\Ledger;

use App\Constants\LedgerConst;
use App\Models\Ledger\SkillLevel;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Append-only: there is deliberately no update or delete here (REQ-002 US-1).
 */
class SkillLevelRepository extends BaseRepository
{
    public function __construct(SkillLevel $model)
    {
        parent::__construct($model);
    }

    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()->with('recorder:id,user_name');

        $this->applyFilters($query, $payload, ['id', 'skill_id']);
        $query->orderByDesc('changed_on')->orderByDesc('id');

        return $query->paginate($payload['per_page'] ?? LedgerConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }

    /**
     * @param  array{skill_id: int, level: int, changed_on: string, reason: ?string, admin_mst_id: ?int}  $attributes
     */
    public function append(array $attributes): SkillLevel
    {
        /** @var SkillLevel */
        return $this->model->newQuery()->create($attributes);
    }

    /**
     * Level of the newest entry by date, then by insertion order (a backdated entry does not win).
     */
    public function latestLevel(int $skillId): int
    {
        /** @var SkillLevel $latest */
        $latest = $this->model->newQuery()
            ->where('skill_id', $skillId)
            ->orderByDesc('changed_on')
            ->orderByDesc('id')
            ->firstOrFail(['level']);

        return $latest->level->value;
    }
}
