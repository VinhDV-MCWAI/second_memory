<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Constants\Messages;
use App\Enums\AdminRole;
use App\Enums\IsActive;
use App\Http\Resources\Master\AdminMstResource;
use App\Repositories\Master\AdminMstRepository;
use App\Services\AuditedCrudService;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

class AdminMstService extends AuditedCrudService
{
    protected string $resource = AdminMstResource::class;

    protected string $auditableType = 'admin';

    public function __construct(
        private readonly AdminMstRepository $adminMst,
        AuditLogger $auditLogger,
    ) {
        parent::__construct($adminMst, $auditLogger);
    }

    /**
     * @throws ValidationException when the last active owner would be demoted or deactivated
     */
    public function update(array $payload): int
    {
        $losesOwnerRights = (isset($payload['role']) && $payload['role'] !== AdminRole::OWNER->value)
            || (isset($payload['is_active']) && (int) $payload['is_active'] !== IsActive::TRUE->value);

        if ($losesOwnerRights) {
            $this->ensureAnOwnerRemains([(int) $payload['id']], 'role');
        }

        return parent::update($payload);
    }

    /**
     * @throws ValidationException when the last active owner would be deleted
     */
    public function delete(array $payload): void
    {
        $this->ensureAnOwnerRemains($payload['ids'] ?? [], 'ids');

        parent::delete($payload);
    }

    /**
     * ADR-0005: the system must always keep one active owner, or nobody could change data again.
     *
     * @param  array<int, int|string>  $changedIds
     *
     * @throws ValidationException
     */
    private function ensureAnOwnerRemains(array $changedIds, string $field): void
    {
        if ($this->adminMst->countActiveOwnersExcept($changedIds) === 0) {
            throw ValidationException::withMessages([$field => Messages::E0021]);
        }
    }
}
