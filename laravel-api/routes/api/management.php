<?php

declare(strict_types=1);

use App\Http\Controllers\Management\MediaMgmtController;
use Illuminate\Support\Facades\Route;

// Media management (MinIO). Loaded inside the authenticated admin group.
Route::get('media-mgmt/list', [MediaMgmtController::class, 'list']);
Route::post('media-mgmt/prepare-upload', [MediaMgmtController::class, 'prepareUpload']);

// Multipart Upload Routes
Route::post('media-mgmt/init-multipart-upload', [MediaMgmtController::class, 'initMultipartUpload']);
Route::post('media-mgmt/get-multipart-url', [MediaMgmtController::class, 'getMultipartUrl']);
Route::post('media-mgmt/complete-multipart-upload', [MediaMgmtController::class, 'completeMultipartUpload']);

Route::post('media-mgmt/store', [MediaMgmtController::class, 'store']);
Route::put('media-mgmt/update/{id}', [MediaMgmtController::class, 'update']);
Route::delete('media-mgmt/delete/{id}', [MediaMgmtController::class, 'delete']);
