<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Public\PublicFileService;

/**
 * No login. GET only, rate-limited per IP (RFC-002 §4.3).
 */
class PublicFileController extends Controller
{
    public function __construct(private readonly PublicFileService $files) {}

    /**
     * Short-lived link to a file attached to a public category or embedded in a public article (its `key`); anything else → 404
     *
     * Images, PDF, video, audio and text open in the browser, other types download.
     *
     * @unauthenticated
     *
     * @return array{url: string, expires_at: string}
     */
    public function url(string $key): array
    {
        return $this->files->url($key);
    }
}
