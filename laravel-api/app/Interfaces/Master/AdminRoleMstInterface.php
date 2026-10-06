<?php

declare(strict_types=1);

namespace App\Interfaces\Master;

use App\Interfaces\BaseInterface;
use Illuminate\Support\Collection;

interface AdminRoleMstInterface extends BaseInterface
{
    /**
     * Get list
     */
    public function list(array $payload): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Store record
     */
    public function executeStore(array $payload): void;

    /**
     * Delete record
     */
    public function executeDelete(array $payload): void;

    /**
     * Get ids
     */
    public function getAdminRoleMstId(array $tuples): Collection;

    /**
     * Check if payload contains current user's role
     */
    public function isMyRole(array $payload): bool;
}
