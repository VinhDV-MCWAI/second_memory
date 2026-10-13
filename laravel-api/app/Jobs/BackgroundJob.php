<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Constants\Messages;
use App\Enums\BackgroundTaskStatus;
use App\Services\BackgroundTasks\BackgroundTaskTracker;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

/**
 * A queued job whose result reaches the admin who started it (ADR-0014). Subclasses implement type()
 * and run(); run() may type-hint dependencies and returns the message shown on screen. Start one with
 * BackgroundTaskTracker::dispatch(), which records it so the screen can read the status back.
 */
abstract class BackgroundJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> seconds before the second and third try */
    public array $backoff = [5, 30];

    public readonly string $taskId;

    public function __construct(public readonly int $adminId, public readonly ?int $subjectId = null)
    {
        $this->taskId = Str::uuid()->toString();
    }

    /** Feature-qualified task type, e.g. `media.process`; the screen picks its message by it. */
    abstract public function type(): string;

    final public function handle(BackgroundTaskTracker $tracker, Container $container): void
    {
        $tracker->running($this->taskId);
        /** @var string $message */
        $message = $container->call([$this, 'run']);
        $tracker->finish($this->taskId, BackgroundTaskStatus::COMPLETED, $message);
    }

    /** Called once every try has failed; the exception itself goes to the log and `failed_jobs`. */
    public function failed(?Throwable $exception): void
    {
        app(BackgroundTaskTracker::class)->finish($this->taskId, BackgroundTaskStatus::FAILED, Messages::E0036);
    }
}
