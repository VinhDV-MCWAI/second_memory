<?php

declare(strict_types=1);

namespace App\Services\Public;

use App\Enums\PreviewKind;
use App\Models\Media\MediaMgmt;
use App\Repositories\Media\MediaRepository;
use App\Services\Media\MediaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Files of the public site (ADR-0022): only those attached to a category the public site shows.
 * Types a browser shows without running code open inline, the rest download (MEDIA-06 rules).
 */
final class PublicFileService
{
    public function __construct(
        private readonly MediaRepository $media,
        private readonly MediaService $mediaService,
    ) {}

    /**
     * A private, deleted or missing file throws the same ModelNotFoundException, so all are 404.
     *
     * @return array{url: string, expires_at: string}
     */
    public function url(string $key): array
    {
        $file = $this->media->publicFile($key) ?? throw (new ModelNotFoundException)->setModel(MediaMgmt::class, [$key]);

        return $this->mediaService->downloadUrl($file->id, PreviewKind::forFile($file->extension, $file->size) !== null);
    }
}
