<?php

declare(strict_types=1);

use App\Http\Controllers\Master\AdminMstController;
use Illuminate\Support\Facades\Route;

// Master data: list / store / update / delete per resource. Loaded inside the authenticated admin group.
foreach ([
    'admin-mst' => AdminMstController::class,
] as $resource => $controller) {
    Route::get("{$resource}/list", [$controller, 'list'])->name("{$resource}.list");
    Route::post("{$resource}/store", [$controller, 'store'])->name("{$resource}.store");
    Route::put("{$resource}/update/{id}", [$controller, 'update'])->name("{$resource}.update");
    Route::post("{$resource}/delete", [$controller, 'delete'])->name("{$resource}.delete");
}
