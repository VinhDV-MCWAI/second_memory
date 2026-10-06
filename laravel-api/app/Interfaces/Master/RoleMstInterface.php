<?php

declare(strict_types=1);

namespace App\Interfaces\Master;

use App\Interfaces\BaseInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RoleMstInterface extends BaseInterface
{
    /**
     * Get list with pagination
     */
    public function list(array $payload): LengthAwarePaginator;

    /**
     * Store record
     */
    public function executeStore(array $payload): int;

    /**
     * Update record
     */
    public function executeUpdate(array $payload): int;

    /**
     * Delete record
     */
    public function executeDelete(array $ids): void;
}
