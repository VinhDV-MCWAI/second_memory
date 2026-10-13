<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\CompleteMultipartUploadRequest;
use App\Http\Requests\Media\PartUrlsRequest;
use App\Http\Requests\Media\StartMultipartUploadRequest;
use App\Http\Resources\System\BackgroundTaskResource;
use App\Services\Media\MultipartUploadService;

/**
 * Large-file upload in parts (ADR-0014): start → part URLs (in batches) → PUT each part to MinIO →
 * complete (a background job joins and checks the file, the result arrives live) or abort.
 */
class MultipartUploadController extends Controller
{
    public function __construct(private readonly MultipartUploadService $uploads) {}

    /**
     * Start a large-file upload (up to 10 GB); returns the part size and number of parts to send
     *
     * @return array{id: int, part_size: int, part_count: int}
     */
    public function start(StartMultipartUploadRequest $request): array
    {
        return $this->uploads->start($request->validated());
    }

    /**
     * Presigned PUT URLs for some parts of your upload (at most 100 per call); read each part's ETag response header
     *
     * @return array{parts: list<array{part_number: int, url: string}>, expires_at: string}
     */
    public function partUrls(PartUrlsRequest $request, string $id): array
    {
        return $this->uploads->partUrls((int) $id, $request->validated('part_numbers'));
    }

    /**
     * Report every part with its ETag; joining and checking the file runs in the background
     */
    public function complete(CompleteMultipartUploadRequest $request, string $id): BackgroundTaskResource
    {
        return new BackgroundTaskResource($this->uploads->complete((int) $id, $request->validated('parts')));
    }

    /**
     * Cancel your unfinished upload and drop the parts already sent
     */
    public function abort(string $id): void
    {
        $this->uploads->abort((int) $id);
    }
}
