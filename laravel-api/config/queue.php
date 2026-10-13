<?php

declare(strict_types=1);

// Background jobs (ADR-0014): queued on Redis, run by the ml-queue container (`queue:work`).
// Tests force the sync driver (phpunit.xml). Jobs that fail every try land in `failed_jobs`
// (`php artisan queue:failed`, `queue:retry`).
return [

    'default' => env('QUEUE_CONNECTION', 'redis'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            // Longer than the worker's --timeout, so a slow job is never handed to a second worker
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 960),
            'block_for' => 5,
            'after_commit' => true,
        ],
    ],

    'failed' => [
        'driver' => 'database-uuids',
        'database' => env('DB_CONNECTION', 'pgsql'),
        'table' => 'failed_jobs',
    ],

];
