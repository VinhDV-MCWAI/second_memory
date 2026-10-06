<?php

use App\Http\Controllers\Custom\CredentialController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Middleware aliases are registered in bootstrap/app.php.
Broadcast::routes(['middleware' => ['api', 'auth.broadcasting'], 'prefix' => 'admin']);

Route::prefix('docs')
    ->middleware('api.response')
    ->group(base_path('routes/api/docs.php'));

Route::prefix('admin')
    ->middleware(['api.response', 'db.transaction'])
    ->group(function () {
        Route::prefix('credential')->group(function () {
            Route::post('login', [CredentialController::class, 'login']);
            Route::prefix('trust')->group(function () {
                Route::post('refresh-token', [CredentialController::class, 'refreshToken']);
            });
        });

        Route::middleware('auth.admin')
            ->group(function () {
                Route::prefix('credential')->group(function () {
                    Route::prefix('trust')->group(function () {
                        Route::post('logout', [CredentialController::class, 'logout']);
                    });
                    Route::get('me', [CredentialController::class, 'me']);
                });

                Route::group([], base_path('routes/api/master.php'));
                Route::group([], base_path('routes/api/management.php'));
                Route::group([], base_path('routes/api/history.php'));
            });
    });
