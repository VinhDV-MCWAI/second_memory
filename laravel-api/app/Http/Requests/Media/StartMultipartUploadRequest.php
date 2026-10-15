<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Constants\MediaConst;

/**
 * Multipart upload of a large file (ADR-0014): same fields as a small upload, up to MAX_FILE_BYTES.
 */
class StartMultipartUploadRequest extends CreateUploadRequest
{
    protected function maxSize(): int
    {
        return MediaConst::MAX_FILE_BYTES;
    }
}
