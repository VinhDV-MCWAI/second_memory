<?php

declare(strict_types=1);

use App\Http\Controllers\Media\MediaController;
use Illuminate\Support\Facades\Route;

// File manager on MinIO (ADR-0014). Loaded inside the authenticated admin group.
// Every route needs a name: it is the permission AdminMiddleware checks (ADR-0013).
Route::get('media/list', [MediaController::class, 'list'])->name('media.list');
Route::post('media/store-folder', [MediaController::class, 'storeFolder'])->name('media.store-folder');
Route::post('media/upload-url', [MediaController::class, 'uploadUrl'])->name('media.upload-url');
Route::post('media/complete/{id}', [MediaController::class, 'complete'])->name('media.complete');
Route::get('media/download-url/{id}', [MediaController::class, 'downloadUrl'])->name('media.download-url');
Route::put('media/update/{id}', [MediaController::class, 'update'])->name('media.update');
Route::post('media/delete', [MediaController::class, 'delete'])->name('media.delete');
