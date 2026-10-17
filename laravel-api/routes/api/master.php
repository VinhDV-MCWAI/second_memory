<?php

declare(strict_types=1);

use App\Http\Controllers\Master\AdminMstController;
use App\Http\Controllers\Master\AdminSessionController;
use App\Http\Controllers\Master\DepartmentController;
use App\Http\Controllers\Master\EndUserController;
use App\Http\Controllers\Master\PermissionController;
use App\Http\Controllers\Master\RoleController;
use Illuminate\Support\Facades\Route;

// Master data: list / store / update / delete per resource. Loaded inside the authenticated admin group.
foreach ([
    'admin-mst' => AdminMstController::class,
    'role' => RoleController::class,
    'department' => DepartmentController::class,
] as $resource => $controller) {
    Route::get("{$resource}/list", [$controller, 'list'])->name("{$resource}.list");
    Route::post("{$resource}/store", [$controller, 'store'])->name("{$resource}.store");
    Route::put("{$resource}/update/{id}", [$controller, 'update'])->name("{$resource}.update");
    Route::post("{$resource}/delete", [$controller, 'delete'])->name("{$resource}.delete");
}

// End users of the public site (ADR-0020): they sign up themselves, so no store route
Route::get('end-user/list', [EndUserController::class, 'list'])->name('end-user.list');
Route::put('end-user/update/{id}', [EndUserController::class, 'update'])->name('end-user.update');
Route::post('end-user/delete', [EndUserController::class, 'delete'])->name('end-user.delete');
Route::get('end-user/registration', [EndUserController::class, 'registration'])->name('end-user.registration');
Route::put('end-user/registration-update', [EndUserController::class, 'updateRegistration'])->name('end-user.registration-update');

// Dynamic permissions (ADR-0013): every permission, grouped by feature, for the permission matrix
Route::get('permission/list', [PermissionController::class, 'list'])->name('permission.list');

// Sessions screen (AUTH-04): the signed-in admin's own devices; in PermissionConst::ALWAYS_ALLOWED
Route::get('admin-session/list', [AdminSessionController::class, 'list'])->name('admin-session.list');
Route::post('admin-session/delete', [AdminSessionController::class, 'delete'])->name('admin-session.delete');
Route::post('admin-session/delete-others', [AdminSessionController::class, 'deleteOthers'])->name('admin-session.delete-others');
