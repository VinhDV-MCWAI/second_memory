<?php

declare(strict_types=1);

// File management on MinIO (ADR-0014). Laravel talks to MinIO on the Docker network (`endpoint`);
// presigned URLs are signed for the host the browser reaches (`public_endpoint`, nginx), because
// the signature covers the host.
return [
    'bucket' => env('AWS_BUCKET', 'media-official'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'endpoint' => env('AWS_ENDPOINT', 'http://ml-minio:9000'),
    'public_endpoint' => env('AWS_PUBLIC_ENDPOINT', 'http://localhost:81'),
];
