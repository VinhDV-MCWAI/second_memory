<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Services\Master\PermissionService;

class PermissionController extends Controller
{
    public function __construct(private readonly PermissionService $permissions) {}

    /**
     * Every permission (one per admin API), grouped by feature.
     *
     * @return list<array{feature: string, permissions: list<array{name: string, action: string, method: string|null}>}>
     */
    public function list(): array
    {
        return $this->permissions->grouped();
    }
}
