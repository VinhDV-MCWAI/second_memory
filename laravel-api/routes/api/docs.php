<?php

declare(strict_types=1);

use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;

// Public docs API (no authentication). Loaded under the `docs` prefix.
Route::get('categories', [DocsController::class, 'getCategories']);
Route::get('categories/{slug}/entries', [DocsController::class, 'getEntriesByCategory']);
Route::get('entries/{slug}', [DocsController::class, 'getEntryDetail']);
Route::get('search', [DocsController::class, 'search']);
