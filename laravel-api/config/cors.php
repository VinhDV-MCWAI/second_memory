<?php

declare(strict_types=1);

return [
    'paths' => ['api/*'],  // Áp dụng cho tất cả routes api
    'allowed_methods' => ['*'],  // Allow methods
    'allowed_origins' => ['http://localhost:81', 'http://localhost:6543', 'http://localhost:3456', 'http://localhost:3457'],  // Specific origins for credentials
    'allowed_origins_patterns' => [],  // Nếu cần regex
    'allowed_headers' => ['*'],
    'exposed_headers' => [],  // Expose nếu cần
    'max_age' => 86400,  // Cache preflight
    'supports_credentials' => true,  // MUST be true for cookie-based auth
];
