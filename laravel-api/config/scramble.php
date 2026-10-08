<?php

declare(strict_types=1);

// Overrides for dedoc/scramble (OpenAPI generator); unset keys keep the package defaults.
// UI: /api/openapi, JSON: /api/openapi.json — local environment only (RestrictedDocsAccess).
// Committed spec: `php artisan scramble:export` writes openapi.json (checked in CI, used by nextjs-fe types).
return [
    'api_path' => [
        'include' => 'api',
        'exclude' => ['api/admin/broadcasting', 'api/openapi', 'api/openapi.json'],
    ],

    'export_path' => 'openapi.json',

    'info' => [
        'version' => '1.0.0',
        'description' => 'Second Memory admin API. Admin routes authenticate with the Sanctum session cookie: call `GET /sanctum/csrf-cookie`, then `POST /admin/credential/login`, and send the `X-XSRF-TOKEN` header on writes (ADR-0004). Every response uses the `{ data, error: { status, code, messages } }` envelope.',
    ],

    // Relative, so the spec works behind any host (nginx serves the API under /api)
    'servers' => [
        'API' => '/api',
    ],

    'ui' => [
        'title' => 'Second Memory API',
    ],
];
