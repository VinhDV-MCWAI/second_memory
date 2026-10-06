<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface BaseInterface
{
    /**
     * Get all record
     */
    public function getAll(): Collection;

    /**
     * Find a record by id
     */
    public function findById(int $id): ?Model;

    /**
     * Store a new record
     */
    public function create(array $data): Model;

    /**
     * Update a record
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a record
     */
    public function delete(int $id): bool;
}
