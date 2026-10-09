<?php

declare(strict_types=1);

// Nothing is queued since ADR-0007 removed the media jobs (API-05): jobs run inline on the sync driver,
// and the jobs / job_batches / failed_jobs tables are dropped.
return [

    'default' => env('QUEUE_CONNECTION', 'sync'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],
    ],

    'failed' => [
        'driver' => 'null',
    ],

];
