<?php

declare(strict_types=1);

namespace App\Interfaces\Master;

use App\Interfaces\BaseInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ApiRoleMstInterface extends BaseInterface
{
    /**
     * Get list
     */
    public function list(array $payload): LengthAwarePaginator;

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
    public function getApiRoleMstId(array $tuples): Collection;

    /**
     * Check if the role belongs to current user
     */
    public function isMyRole(array $payload): bool;
}
