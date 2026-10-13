<?php

declare(strict_types=1);

namespace App\Services\BackgroundTasks;

use App\Constants\BackgroundTaskConst;
use App\Enums\BackgroundTaskStatus;
use App\Events\BackgroundTaskFinished;
use App\Jobs\BackgroundJob;
use Illuminate\Support\Facades\Cache;

/**
 * Status of background tasks, kept in the cache for a day (ADR-0014). The screen gets a live event when
 * a task finishes, and can read the status back if it connected too late to receive it.
 *
 * @phpstan-type Task array{id: string, type: string, subject_id: int|null, status: string, message: string|null, admin_id: int, updated_at: string}
 */
final class BackgroundTaskTracker
{
    /**
     * Records the task as queued, then queues the job (after the request's transaction commits).
     *
     * @return Task
     */
    public function dispatch(BackgroundJob $job): array
    {
        $task = $this->save([
            'id' => $job->taskId,
            'type' => $job->type(),
            'subject_id' => $job->subjectId,
            'status' => BackgroundTaskStatus::QUEUED->value,
            'message' => null,
            'admin_id' => $job->adminId,
        ]);
        dispatch($job);

        return $task;
    }

    public function running(string $id): void
    {
        $this->update($id, BackgroundTaskStatus::RUNNING, null);
    }

    /** Stores the result and broadcasts it to the admin who started the task. */
    public function finish(string $id, BackgroundTaskStatus $status, ?string $message): void
    {
        $task = $this->update($id, $status, $message);
        if ($task !== null) {
            BackgroundTaskFinished::dispatch($task);
        }
    }

    /**
     * A task of this admin, or null (unknown, expired, or someone else's).
     *
     * @return Task|null
     */
    public function find(string $id, int $adminId): ?array
    {
        $task = Cache::get(BackgroundTaskConst::CACHE_PREFIX.$id);

        return is_array($task) && $task['admin_id'] === $adminId ? $task : null;
    }

    /**
     * @return Task|null
     */
    private function update(string $id, BackgroundTaskStatus $status, ?string $message): ?array
    {
        $task = Cache::get(BackgroundTaskConst::CACHE_PREFIX.$id);
        if (! is_array($task)) {
            return null;
        }

        return $this->save([...$task, 'status' => $status->value, 'message' => $message]);
    }

    /**
     * @param  array<string, mixed>  $task
     * @return Task
     */
    private function save(array $task): array
    {
        $task['updated_at'] = now()->toIso8601String();
        Cache::put(BackgroundTaskConst::CACHE_PREFIX.$task['id'], $task, BackgroundTaskConst::STATUS_TTL_SECONDS);

        /** @var Task $task */
        return $task;
    }
}
