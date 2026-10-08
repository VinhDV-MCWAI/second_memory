<?php

declare(strict_types=1);

namespace App\Services\Ledger;

use App\Http\Resources\Ledger\SkillResource;
use App\Repositories\Ledger\SkillRepository;
use App\Services\AuditedCrudService;
use App\Services\AuditLogger;
use Illuminate\Support\Arr;

class SkillService extends AuditedCrudService
{
    protected string $resource = SkillResource::class;

    protected string $auditableType = 'skill';

    public function __construct(
        SkillRepository $skills,
        AuditLogger $auditLogger,
        private readonly SkillLevelService $levels,
    ) {
        parent::__construct($skills, $auditLogger);
    }

    /**
     * Creates the skill and its first level entry in the request's transaction.
     */
    public function store(array $payload): int
    {
        $levelFields = ['level', 'changed_on', 'reason'];
        $id = parent::store([...Arr::except($payload, $levelFields), 'current_level' => (int) $payload['level']]);
        $this->levels->record(['skill_id' => $id, ...Arr::only($payload, $levelFields)]);

        return $id;
    }
}
