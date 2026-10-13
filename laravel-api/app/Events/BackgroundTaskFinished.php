<?php

declare(strict_types=1);

namespace App\Events;

use App\Constants\BackgroundTaskConst;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Sent to the admin who started a background task when it completes or fails (ADR-0014). One event
 * for every feature: the screen tells tasks apart by `type` and `subject_id`. Broadcast right away:
 * it is fired from inside the job already.
 */
final class BackgroundTaskFinished implements ShouldBroadcastNow
{
    use Dispatchable;

    /**
     * @param  array{id: string, type: string, subject_id: int|null, status: string, message: string|null, admin_id: int, updated_at: string}  $task
     */
    public function __construct(public readonly array $task) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(BackgroundTaskConst::CHANNEL_PREFIX.$this->task['admin_id']);
    }

    public function broadcastAs(): string
    {
        return BackgroundTaskConst::FINISHED_EVENT;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $task = $this->task;
        unset($task['admin_id']);

        return $task;
    }
}
