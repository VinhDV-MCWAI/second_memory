<?php

declare(strict_types=1);

namespace App\Jobs\Media;

use App\Constants\MediaConst;
use App\Jobs\BackgroundJob;
use App\Models\Media\MediaMgmt;
use App\Services\Media\MultipartUploadService;
use Throwable;

/**
 * After a multipart upload (ADR-0014): joins the parts in MinIO, checks the size, publishes the file.
 * Joining a file of several GB can take longer than a request may, hence a job.
 */
final class ProcessUploadJob extends BackgroundJob
{
    /**
     * @param  list<array{part_number: int, etag: string}>  $parts
     */
    public function __construct(int $adminId, int $mediaId, public readonly array $parts)
    {
        parent::__construct($adminId, $mediaId);
    }

    public function type(): string
    {
        return MediaConst::TASK_PROCESS;
    }

    public function run(MultipartUploadService $uploads): string
    {
        $file = $uploads->process((int) $this->subjectId, $this->parts, $this->adminId);

        return $file === null ? 'The upload was removed before it finished' : "{$file->original_name} is ready";
    }

    public function failed(?Throwable $exception): void
    {
        $file = MediaMgmt::query()->live()->find($this->subjectId);
        if ($file !== null) {
            app(MultipartUploadService::class)->fail($file);
        }
        parent::failed($exception);
    }
}
