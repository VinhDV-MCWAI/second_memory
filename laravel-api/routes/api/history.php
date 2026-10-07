<?php

declare(strict_types=1);

use App\Http\Controllers\History\Management\CategoryMgmtHistController;
use App\Http\Controllers\History\Management\EntryDescriptionMgmtHistController;
use App\Http\Controllers\History\Management\EntryMgmtHistController;
use App\Http\Controllers\History\Master\AdminMstHistController;
use App\Http\Controllers\History\Master\ApiMstHistController;
use App\Http\Controllers\History\Master\FeatureMstHistController;
use App\Http\Controllers\History\Master\RoleMstHistController;
use Illuminate\Support\Facades\Route;

// History (audit trail): list / store / update / delete per history table. Loaded inside the authenticated admin group.
foreach ([
    'admin-mst-hist' => AdminMstHistController::class,
    'api-mst-hist' => ApiMstHistController::class,
    'feature-mst-hist' => FeatureMstHistController::class,
    'role-mst-hist' => RoleMstHistController::class,
    'category-mgmt-hist' => CategoryMgmtHistController::class,
    'entry-description-mgmt-hist' => EntryDescriptionMgmtHistController::class,
    'entry-mgmt-hist' => EntryMgmtHistController::class,
] as $resource => $controller) {
    Route::get("{$resource}/list", [$controller, 'list']);
    Route::post("{$resource}/store", [$controller, 'store']);
    Route::put("{$resource}/update/{id}", [$controller, 'update']);
    Route::post("{$resource}/delete", [$controller, 'delete']);
}
