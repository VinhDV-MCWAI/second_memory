<?php

declare(strict_types=1);

use App\Constants\EndUserConst;
use App\Constants\LedgerConst;
use App\Http\Controllers\Audit\AuditLogController;
use App\Http\Controllers\Custom\CredentialController;
use App\Http\Controllers\Ledger\EvidenceImportController;
use App\Http\Controllers\Public\PublicFileController;
use App\Http\Controllers\Public\PublicSkillController;
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
                        Route::group([], base_path('routes/api/ledger.php'));
                        Route::group([], base_path('routes/api/media.php'));
                        Route::group([], base_path('routes/api/system.php'));
                        Route::get('audit-log/list', [AuditLogController::class, 'list'])->name('audit-log.list');
                    });

                // The only route an API token opens (ADR-0010): the Obsidian importer CLI, or the owner's session
                Route::post('evidence/import', [EvidenceImportController::class, 'import'])
                    ->name('evidence.import')
                    ->middleware(['auth.admin:'.LedgerConst::IMPORT_ABILITY, 'throttle:'.LedgerConst::IMPORT_RATE_PER_MINUTE.',1']);
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
        });
    });

// Public, read-only Skill Ledger (RFC-002 §4.3): no session, GET only, rate-limited per IP
Route::prefix('public')
    ->middleware(['api.response', 'throttle:public'])
    ->group(function () {
        Route::get('skills', [PublicSkillController::class, 'list']);
        Route::get('skills/{slug}', [PublicSkillController::class, 'show']);
        // Files attached to public evidence (LEDGER-01)
        Route::get('files/{key}', [PublicFileController::class, 'url'])->whereUuid('key');
    });
