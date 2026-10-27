<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Http\Resources\Master\DepartmentResource;
use App\Repositories\Master\DepartmentRepository;
use App\Services\AuditedCrudService;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;

class DepartmentService extends AuditedCrudService
{
    protected string $resource = DepartmentResource::class;

    protected string $auditableType = 'department';

    public function __construct(DepartmentRepository $departments, AuditLogger $auditLogger) // NOSONAR php:S1185: the parent takes the abstract CrudRepository; this names the one the container injects
    {
        parent::__construct($departments, $auditLogger);
    }

    public function store(array $payload): int
    {
        return parent::store([...$payload, 'created_by' => Auth::id(), 'updated_by' => Auth::id()]);
    }

    public function update(array $payload): int
    {
        return parent::update([...$payload, 'updated_by' => Auth::id()]);
    }
}
