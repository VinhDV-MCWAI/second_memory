<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Constants\Messages;
use App\Enums\AdminRole;
use App\Http\Resources\Master\RoleResource;
use App\Repositories\Master\RoleRepository;
use App\Services\AuditedCrudService;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Roles of the dynamic permissions (ADR-0013). The built-in roles keep their names, because the
 * code finds them by name: `owner` passes every check, `viewer` is the default of a new admin.
 */
class RoleService extends AuditedCrudService
{
    protected string $resource = RoleResource::class;

    protected string $auditableType = 'role';

    public function __construct(private readonly RoleRepository $roles, AuditLogger $auditLogger) // NOSONAR php:S1185: the parent takes the abstract CrudRepository; this names the one the container injects
    {
        parent::__construct($roles, $auditLogger);
    }

    /**
     * @throws ValidationException when a built-in role is renamed or the owner role's permissions change
     */
    public function update(array $payload): int
    {
        $current = $this->roles->nameOf((int) $payload['id']);
        if ($this->isBuiltIn($current) && isset($payload['name']) && $payload['name'] !== $current) {
            throw ValidationException::withMessages(['name' => Messages::E0025]);
        }
        if ($current === AdminRole::OWNER->value && array_key_exists('permissions', $payload)) {
            throw ValidationException::withMessages(['permissions' => Messages::E0026]);
        }

        return parent::update($payload);
    }

    /**
     * @throws ValidationException when a built-in role or a role still held by an admin would be deleted
     */
    public function delete(array $payload): void
    {
        $ids = $payload['ids'] ?? [];
        foreach ($this->roles->namesOf($ids) as $name) {
            if ($this->isBuiltIn($name)) {
                throw ValidationException::withMessages(['ids' => Messages::E0025]);
            }
        }
        if ($this->roles->anyAssigned($ids)) {
            throw ValidationException::withMessages(['ids' => Messages::E0027]);
        }

        parent::delete($payload);
    }

    private function isBuiltIn(string $name): bool
    {
        return AdminRole::tryFrom($name) !== null;
    }
}
