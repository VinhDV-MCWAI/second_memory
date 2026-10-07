<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Constants\CommonVal;
use App\Constants\Messages;
use App\Http\Resources\Master\AdminRoleMstResource;
use App\Repositories\Master\AdminRoleMstRepository;
use App\Services\BaseJunctionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LogicException;

class AdminRoleMstService extends BaseJunctionService
{
    public function __construct(
        protected AdminRoleMstRepository $adminRoleMst
    ) {}

    /**
     * Get admin role mst list
     */
    public function list(array $payload): AnonymousResourceCollection
    {
        $list = $this->adminRoleMst->list($payload);

        return AdminRoleMstResource::collection($list);
    }

    /**
     * Update admin role mst
     */
    public function update(array $payload): bool
    {
        // Don't allow editing of personal role without role admin
        if ($this->adminRoleMst->isMyRole($payload)) {
            throw new LogicException(Messages::E0018, CommonVal::HTTP_UNPROCESSABLE_CONTENT);
        }

        // Delete admin role mst
        if (isset($payload['delete']) && $payload['delete']) {
            $this->validateExistence(
                $payload['delete'],
                fn ($values) => $this->adminRoleMst->getAdminRoleMstId($values),
                'admin_role_id',
                'admin_role_mst'
            );
            $this->adminRoleMst->executeDelete($payload['delete']);
        }

        // Insert admin role mst
        if (isset($payload['insert']) && $payload['insert']) {
            $this->validateNonExistence(
                $payload['insert'],
                fn ($values) => $this->adminRoleMst->getAdminRoleMstId($values),
                'admin_role_id',
                'admin_role_mst'
            );
            $this->adminRoleMst->executeStore($payload['insert']);
        }

        return true;
    }
}
