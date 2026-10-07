<?php

declare(strict_types=1);

use App\Http\Controllers\Custom\CredentialController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Middleware aliases are registered in bootstrap/app.php. Auth: Sanctum SPA session (ADR-0004).
Broadcast::routes(['middleware' => ['api', 'auth:sanctum'], 'prefix' => 'admin']);

Route::prefix('admin')
    ->middleware(['api.response', 'db.transaction'])
    ->group(function () {
        Route::prefix('credential')->group(function () {
            Route::post('login', [CredentialController::class, 'login']);
            Route::post('logout', [CredentialController::class, 'logout']);
        });

        Route::middleware('auth:sanctum')
            ->group(function () {
                Route::get('credential/me', [CredentialController::class, 'me']);

                Route::middleware('auth.admin')
                    ->group(function () {
                        Route::group([], base_path('routes/api/master.php'));
                        Route::group([], base_path('routes/api/management.php'));
                        Route::group([], base_path('routes/api/history.php'));
                    });
            });
    });
