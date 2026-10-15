<?php

declare(strict_types=1);

namespace App\Services\Public;

use App\Enums\PreviewKind;
use App\Models\Media\MediaMgmt;
use App\Repositories\Media\MediaRepository;
use App\Services\Media\MediaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Files of the public Skill Ledger (LEDGER-01): only those attached to public evidence of a public
 * skill. Types a browser shows without running code open inline, the rest download (MEDIA-06 rules).
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
