<?php

declare(strict_types=1);

namespace App\Interfaces\Management;

use App\Interfaces\BaseInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface EntryMgmtInterface extends BaseInterface
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
     * Get entries by category slug
     */
    public function getEntriesByCategorySlug(string $slug): Collection;

    /**
     * Get entry detail by slug with descriptions
     */
    public function getEntryDetailBySlug(string $slug): ?Model;

    /**
     * Search entries
     */
    public function searchEntries(string $query): Collection;
}
