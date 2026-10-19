<?php

declare(strict_types=1);

use App\Http\Controllers\Content\ArticleController;
use App\Http\Controllers\Content\ContentCategoryController;
use Illuminate\Support\Facades\Route;

// Categories and articles of the public site (ADR-0021). Loaded inside the authenticated admin group;
// every route name is its permission (ADR-0013).
foreach ([
    'content-category' => ContentCategoryController::class,
    'article' => ArticleController::class,
] as $resource => $controller) {
    Route::get("{$resource}/list", [$controller, 'list'])->name("{$resource}.list");
    Route::post("{$resource}/store", [$controller, 'store'])->name("{$resource}.store");
    Route::put("{$resource}/update/{id}", [$controller, 'update'])->name("{$resource}.update");
    Route::post("{$resource}/delete", [$controller, 'delete'])->name("{$resource}.delete");
}
