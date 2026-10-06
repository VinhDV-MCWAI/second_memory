<?php

namespace App\Events;

use App\Enums\UploadStatus;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UploadStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ?int $userId,
        public string $roomId,
        public int $status,
        public string $message,
        public ?int $fileId = null,
        public ?string $url = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast to a private channel for the specific room/user context
        // Ideally this matches what the frontend subscribes to.
        // Frontend will subscribe to `private-upload.status.{roomId}`
        return [
            new PrivateChannel('upload.status.'.$this->roomId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'upload.status.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'roomId' => $this->roomId,
            'status' => $this->status, // Int from Enum
            'statusLabel' => UploadStatus::tryFrom($this->status)?->label() ?? 'Unknown',
            'message' => $this->message,
            'fileId' => $this->fileId,
            'url' => $this->url,
        ];
    }
}
