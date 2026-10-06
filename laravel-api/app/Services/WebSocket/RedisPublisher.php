<?php

declare(strict_types=1);

namespace App\Services\WebSocket;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RedisPublisher
{
    /**
     * Publish a message to a Redis channel.
     */
    public function publish(string $channel, array $data): void
    {
        try {
            Redis::publish($channel, json_encode($data));
            Log::info("Published to Redis channel {$channel}", $data);
        } catch (\Exception $e) {
            Log::error("Failed to publish to Redis channel {$channel}", [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }
}
