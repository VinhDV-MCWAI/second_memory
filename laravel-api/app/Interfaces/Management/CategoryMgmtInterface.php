<?php

declare(strict_types=1);

namespace App\Interfaces\Management;

use App\Interfaces\BaseInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CategoryMgmtInterface extends BaseInterface
{
    /**
     * Get list
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

    /**
     * Get displayable categories for docs
     */
    public function getDisplayableCategories(): \Illuminate\Support\Collection;

    /**
     * Search categories
     */
    public function searchCategories(string $query): \Illuminate\Support\Collection;
}
