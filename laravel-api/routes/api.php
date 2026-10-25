<?php

declare(strict_types=1);

use App\Constants\ContentConst;
use App\Constants\EndUserConst;
use App\Http\Controllers\Audit\AuditLogController;
use App\Http\Controllers\Content\ArticleImportController;
use App\Http\Controllers\Custom\CredentialController;
use App\Http\Controllers\Public\PublicContentController;
use App\Http\Controllers\Public\PublicFileController;
use App\Http\Controllers\User\EndUserController;
use App\Http\Controllers\User\SavedController;
use Illuminate\Support\Facades\Route;

// Middleware aliases are registered in bootstrap/app.php. Auth: Sanctum SPA session (ADR-0004).

// No db.transaction here: a failed login throws, and its audit_log row must not be rolled back
Route::prefix('admin/credential')
    ->middleware('api.response')
    ->group(function () {
        Route::post('login', [CredentialController::class, 'login']);
        Route::post('logout', [CredentialController::class, 'logout']);
    });

Route::prefix('admin')
    ->middleware(['api.response', 'db.transaction'])
    ->group(function () {
        // A remembered device (AUTH-05) whose session expired is signed in again first
        Route::middleware(['admin.remember', 'auth:sanctum'])
            ->group(function () {
                Route::middleware(['admin.session', 'auth.admin'])
                    ->group(function () {
                        Route::get('credential/me', [CredentialController::class, 'me'])->name('credential.me');
                        Route::group([], base_path('routes/api/master.php'));
                        Route::group([], base_path('routes/api/media.php'));
                        Route::group([], base_path('routes/api/system.php'));
                        Route::group([], base_path('routes/api/content.php'));
                        Route::get('audit-log/list', [AuditLogController::class, 'list'])->name('audit-log.list');
                    });

                // The only route an API token opens (ADR-0010, ADR-0022): the Obsidian importer CLI, or the owner's session
                Route::post('article/import', [ArticleImportController::class, 'import'])
                    ->name('article.import')
                    ->middleware(['auth.admin:'.ContentConst::IMPORT_ABILITY, 'throttle:'.ContentConst::IMPORT_RATE_PER_MINUTE.',1']);
            });
    });

// End users of the public site (ADR-0020): their own guard, never the admin routes. Session + CSRF
// as for the admin SPA (Sanctum stateful domains); rate-limited per IP.
Route::prefix('user')
    ->middleware(['api.response', 'db.transaction', 'throttle:user'])
    ->group(function () {
        Route::get('registration', [EndUserController::class, 'registration']);
        Route::post('register', [EndUserController::class, 'register'])->middleware('throttle:user-register');
        Route::post('login', [EndUserController::class, 'login']);
        Route::post('logout', [EndUserController::class, 'logout']);

        Route::middleware('auth:'.EndUserConst::GUARD)->group(function () {
            Route::get('me', [EndUserController::class, 'me']);
            Route::put('password', [EndUserController::class, 'password']);
            Route::post('delete-account', [EndUserController::class, 'deleteAccount']);
            Route::get('saved/list', [SavedController::class, 'list']);
            Route::post('saved/store', [SavedController::class, 'store']);
            Route::post('saved/delete', [SavedController::class, 'delete']);
            Route::post('saved/position', [SavedController::class, 'position']);
        });
    });

// Public, read-only site (ADR-0021, ADR-0022): no session, GET only, rate-limited per IP
Route::prefix('public')
    ->middleware(['api.response', 'throttle:public'])
    ->group(function () {
        // Files the public site shows (ADR-0022)
        Route::get('files/{key}', [PublicFileController::class, 'url'])->whereUuid('key');
        // Categories and articles (ADR-0022)
        Route::get('home', [PublicContentController::class, 'home']);
        Route::get('categories', [PublicContentController::class, 'categories']);
        Route::get('articles', [PublicContentController::class, 'articles']);
        Route::get('categories/{slug}', [PublicContentController::class, 'category']);
        Route::get('articles/{slug}', [PublicContentController::class, 'article']);
        Route::get('search', [PublicContentController::class, 'search']);
    });

// View counts of articles and categories (ADR-0022, ADR-0024), reported by the public site's server: its own rate limit
Route::prefix('public')
    ->middleware(['api.response', 'throttle:public-view'])
    ->group(function () {
        Route::post('articles/{slug}/view', [PublicContentController::class, 'view']);
        Route::post('categories/{slug}/view', [PublicContentController::class, 'viewCategory']);
    });
