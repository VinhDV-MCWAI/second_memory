<?php

declare(strict_types=1);

namespace App\Interfaces\History\Master;

use App\Interfaces\BaseInterface;

interface ApiMstHistInterface extends BaseInterface
{
    /**
     * Get list
     */
    public function list(array $payload): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
