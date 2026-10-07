<?php

declare(strict_types=1);

use App\Http\Controllers\Master\AdminMstController;
use App\Http\Controllers\Master\AdminRoleMstController;
use App\Http\Controllers\Master\ApiMstController;
use App\Http\Controllers\Master\ApiRoleMstController;
use App\Http\Controllers\Master\FeatureMstController;
use App\Http\Controllers\Master\RoleMstController;
use App\Http\Controllers\Master\TokenMstController;
use Illuminate\Support\Facades\Route;

// Master data: list / store / update / delete per resource. Loaded inside the authenticated admin group.
foreach ([
    'admin-mst' => AdminMstController::class,
    'role-mst' => RoleMstController::class,
    'feature-mst' => FeatureMstController::class,
    'api-mst' => ApiMstController::class,
    'token-mst' => TokenMstController::class,
] as $resource => $controller) {
    Route::get("{$resource}/list", [$controller, 'list']);
    Route::post("{$resource}/store", [$controller, 'store']);
    Route::put("{$resource}/update/{id}", [$controller, 'update']);
    Route::post("{$resource}/delete", [$controller, 'delete']);
}

// Junction tables (many-to-many): list + bulk update
foreach ([
    'admin-role-mst' => AdminRoleMstController::class,
    'api-role-mst' => ApiRoleMstController::class,
] as $resource => $controller) {
    Route::get("{$resource}/list", [$controller, 'list']);
    Route::put("{$resource}/update", [$controller, 'update']);
}
