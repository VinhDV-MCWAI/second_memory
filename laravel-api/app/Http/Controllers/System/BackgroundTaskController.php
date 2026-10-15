<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\TestBackgroundTaskRequest;
use App\Http\Resources\System\BackgroundTaskResource;
use App\Jobs\System\TestBackgroundJob;
use App\Services\BackgroundTasks\BackgroundTaskTracker;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Background jobs and live notifications (ADR-0014).
 */
class BackgroundTaskController extends Controller
{
    public function __construct(private readonly BackgroundTaskTracker $tracker) {}

    /**
     * Queue a test job that finishes (or fails, with `fail`) after `seconds`; the result arrives as a live event
     */
    public function test(TestBackgroundTaskRequest $request): BackgroundTaskResource
    {
        $payload = $request->validated();
        $job = new TestBackgroundJob((int) Auth::id(), (int) ($payload['seconds'] ?? 3), (bool) ($payload['fail'] ?? false));

        return new BackgroundTaskResource($this->tracker->dispatch($job));
    }

    /**
     * Status of one of your background tasks (kept one day), for a screen that missed the live event
     */
    public function status(string $id): BackgroundTaskResource
    {
        $task = $this->tracker->find($id, (int) Auth::id());
        if ($task === null) {
            throw new NotFoundHttpException;
        }

        return new BackgroundTaskResource($task);
    }
}
