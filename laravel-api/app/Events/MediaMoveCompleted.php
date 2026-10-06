<?php

namespace App\Events;

use App\Models\Management\MediaMgmt;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MediaMoveCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $media;

    /**
     * Create a new event instance.
     */
    public function __construct(MediaMgmt $media)
    {
        $this->media = $media;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        if ($this->media->workspace_id) {
            return [
                new PrivateChannel('workspace.'.$this->media->workspace_id),
            ];
        }

        // Fallback for personal files if any
        return [
            new PrivateChannel('user.'.$this->media->created_by),
        ];
    }
}
