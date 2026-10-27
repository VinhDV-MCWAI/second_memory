<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Enums\IsActive;
use App\Http\Resources\Master\AdminMstResource;
use App\Models\Master\AdminMst;
use App\Repositories\Master\AdminMstRepository;
use App\Services\AuditedCrudService;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class AdminMstService extends AuditedCrudService
{
    protected string $resource = AdminMstResource::class;

    protected string $auditableType = 'admin';

    public function __construct(
        private readonly AdminMstRepository $adminMst,
        AuditLogger $auditLogger,
        private readonly AdminSessionService $adminSessions,
    ) {
        parent::__construct($adminMst, $auditLogger);
    }

    /**
     * @throws ValidationException when the last active owner would be demoted or deactivated
     */
    public function update(array $payload): int
    {
        $losesOwnerRights = (isset($payload['role_ids']) && ! $this->adminMst->includesOwnerRole($payload['role_ids']))
            || (isset($payload['is_active']) && (int) $payload['is_active'] !== IsActive::TRUE->value);

        if ($losesOwnerRights) {
            $this->ensureAnOwnerRemains([(int) $payload['id']], 'role_ids');
        }

        $id = parent::update($payload);
        $isSelf = (int) $payload['id'] === (int) Auth::id();

        // The session keeps a hash of the password (Sanctum AuthenticateSession) and other sessions
        // with the old hash are logged out. Refresh the current admin so this session keeps working.
        $current = Auth::guard(CommonVal::ADMIN_GUARD)->user();
        if (! empty($payload['password']) && $isSelf && $current instanceof AdminMst) {
            $current->refresh();
        }

        // Ended sessions also leave the sessions screen (AUTH-04): a new password ends the admin's other
        // devices, deactivating ends every device
        if (isset($payload['is_active']) && (int) $payload['is_active'] !== IsActive::TRUE->value) {
            $this->adminSessions->endAll((int) $payload['id']);
        } elseif (! empty($payload['password'])) {
            $this->adminSessions->endAll((int) $payload['id'], $isSelf ? Session::getId() : null);
        }

        return $id;
    }

    /**
     * @throws ValidationException when the last active owner would be deleted
     */
    public function delete(array $payload): void
    {
        $this->ensureAnOwnerRemains($payload['ids'] ?? [], 'ids');

        parent::delete($payload);

        foreach ($payload['ids'] ?? [] as $id) {
            $this->adminSessions->endAll((int) $id);
        }
    }

    /**
     * ADR-0013: the system must always keep one active owner, or nobody could change data again.
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
