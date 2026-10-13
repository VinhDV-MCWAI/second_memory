<?php

declare(strict_types=1);

use App\Http\Controllers\System\BackgroundTaskController;
use Illuminate\Support\Facades\Route;

// Background jobs and live notifications (ADR-0014). Loaded inside the authenticated admin group.
// Every route needs a name: it is the permission AdminMiddleware checks (ADR-0013).
Route::post('background-task/test', [BackgroundTaskController::class, 'test'])->name('background-task.test');
Route::get('background-task/status/{id}', [BackgroundTaskController::class, 'status'])->name('background-task.status');
