<?php

declare(strict_types=1);

use App\Http\Controllers\Management\BannerMgmtController;
use App\Http\Controllers\Management\CategoryMgmtController;
use App\Http\Controllers\Management\EntryDescriptionMgmtController;
use App\Http\Controllers\Management\EntryMgmtController;
use App\Http\Controllers\Management\MediaMgmtController;
use App\Http\Controllers\Management\SettingLinkMgmtController;
use App\Http\Controllers\Management\SliderMgmtController;
use App\Http\Controllers\Management\SocialMgmtController;
use App\Http\Controllers\Management\UserMgmtController;
use Illuminate\Support\Facades\Route;

// Management data: list / store / update / delete per resource. Loaded inside the authenticated admin group.
foreach ([
    'banner-mgmt' => BannerMgmtController::class,
    'category-mgmt' => CategoryMgmtController::class,
    'entry-mgmt' => EntryMgmtController::class,
    'entry-description-mgmt' => EntryDescriptionMgmtController::class,
    'slider-mgmt' => SliderMgmtController::class,
    'social-mgmt' => SocialMgmtController::class,
    'user-mgmt' => UserMgmtController::class,
    'setting-link-mgmt' => SettingLinkMgmtController::class,
] as $resource => $controller) {
    Route::get("{$resource}/list", [$controller, 'list']);
    Route::post("{$resource}/store", [$controller, 'store']);
    Route::put("{$resource}/update/{id}", [$controller, 'update']);
    Route::post("{$resource}/delete", [$controller, 'delete']);
}

// Media Management (MinIO-based File Manager)
Route::get('media-mgmt/list', [MediaMgmtController::class, 'list']);
Route::post('media-mgmt/prepare-upload', [MediaMgmtController::class, 'prepareUpload']);

// Multipart Upload Routes
Route::post('media-mgmt/init-multipart-upload', [MediaMgmtController::class, 'initMultipartUpload']);
Route::post('media-mgmt/get-multipart-url', [MediaMgmtController::class, 'getMultipartUrl']);
Route::post('media-mgmt/complete-multipart-upload', [MediaMgmtController::class, 'completeMultipartUpload']);

Route::post('media-mgmt/store', [MediaMgmtController::class, 'store']);
Route::put('media-mgmt/update/{id}', [MediaMgmtController::class, 'update']);
Route::delete('media-mgmt/delete/{id}', [MediaMgmtController::class, 'delete']);
