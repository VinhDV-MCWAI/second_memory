<?php

declare(strict_types=1);

use App\Constants\LedgerConst;
use App\Http\Controllers\Audit\AuditLogController;
use App\Http\Controllers\Custom\CredentialController;
use App\Http\Controllers\Ledger\EvidenceImportController;
use App\Http\Controllers\Public\PublicSkillController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Middleware aliases are registered in bootstrap/app.php. Auth: Sanctum SPA session (ADR-0004).
Broadcast::routes(['middleware' => ['api', 'auth:sanctum'], 'prefix' => 'admin']);

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
        Route::middleware('auth:sanctum')
            ->group(function () {
                Route::middleware('auth.admin')
                    ->group(function () {
                        Route::get('credential/me', [CredentialController::class, 'me']);
                        Route::group([], base_path('routes/api/master.php'));
                        Route::group([], base_path('routes/api/ledger.php'));
                        Route::get('audit-log/list', [AuditLogController::class, 'list']);
                    });

                // The only route an API token opens (ADR-0010): the Obsidian importer CLI, or the owner's session
                Route::post('evidence/import', [EvidenceImportController::class, 'import'])
                    ->middleware(['auth.admin:'.LedgerConst::IMPORT_ABILITY, 'throttle:'.LedgerConst::IMPORT_RATE_PER_MINUTE.',1']);
            });
    });

// Public, read-only Skill Ledger (RFC-002 §4.3): no session, GET only, rate-limited per IP
Route::prefix('public')
    ->middleware(['api.response', 'throttle:public'])
    ->group(function () {
        Route::get('skills', [PublicSkillController::class, 'list']);
        Route::get('skills/{slug}', [PublicSkillController::class, 'show']);
    });
