<?php

declare(strict_types=1);

use App\Http\Controllers\Content\ArticleController;
use App\Http\Controllers\Content\ContentCategoryController;
use App\Http\Controllers\Content\DashboardController;
use App\Http\Controllers\Content\SearchController;
use Illuminate\Support\Facades\Route;

// Categories and articles of the public site (ADR-0021, ADR-0022). Loaded inside the authenticated admin group;
// every route name is its permission (ADR-0013).
foreach ([
    'content-category' => ContentCategoryController::class,
    'article' => ArticleController::class,
] as $resource => $controller) {
    Route::get("{$resource}/list", [$controller, 'list'])->name("{$resource}.list");
    Route::post("{$resource}/store", [$controller, 'store'])->name("{$resource}.store");
    Route::put("{$resource}/update/{id}", [$controller, 'update'])->name("{$resource}.update");
    Route::post("{$resource}/delete", [$controller, 'delete'])->name("{$resource}.delete");
    // The ↑ ↓ buttons among siblings (ADR-0022)
    Route::put("{$resource}/move/{id}", [$controller, 'move'])->name("{$resource}.move");
}

// Markdown as the public page shows it, for the editor's Preview tab (ADR-0022)
Route::post('article/preview', [ArticleController::class, 'preview'])->name('article.preview');

// One search box (ADR-0009)
Route::get('search', [SearchController::class, 'search'])->name('search.query');

Route::get('dashboard/summary', [DashboardController::class, 'summary'])->name('dashboard.summary');
