<?php

namespace App\Interfaces\Management;

use Illuminate\Support\Collection;

interface MediaMgmtInterface
{
    public function list(array $payload): Collection;

    public function find(int $id);

    public function executeStore(array $payload): int;

    public function executeUpdate(array $payload): int;

    public function executeDelete(array $ids): void;
}
