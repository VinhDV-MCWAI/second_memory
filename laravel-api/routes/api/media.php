<?php

declare(strict_types=1);

use App\Http\Controllers\Media\MediaController;
use App\Http\Controllers\Media\MultipartUploadController;
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

// Large files in parts (multipart upload); the joined file is processed by a background job
Route::post('media/multipart/start', [MultipartUploadController::class, 'start'])->name('media.multipart-start');
Route::post('media/multipart/part-urls/{id}', [MultipartUploadController::class, 'partUrls'])->name('media.multipart-part-urls');
Route::post('media/multipart/complete/{id}', [MultipartUploadController::class, 'complete'])->name('media.multipart-complete');
Route::get('media/multipart/unfinished', [MultipartUploadController::class, 'unfinished'])->name('media.multipart-unfinished');
Route::post('media/multipart/resume/{id}', [MultipartUploadController::class, 'resume'])->name('media.multipart-resume');
Route::post('media/multipart/abort/{id}', [MultipartUploadController::class, 'abort'])->name('media.multipart-abort');
