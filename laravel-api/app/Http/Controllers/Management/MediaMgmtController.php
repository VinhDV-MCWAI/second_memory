<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\MediaMgmt\CompleteMultipartUploadRequest;
use App\Http\Requests\Management\MediaMgmt\DeleteMediaMgmtRequest;
use App\Http\Requests\Management\MediaMgmt\GetMultipartUrlRequest;
use App\Http\Requests\Management\MediaMgmt\InitMultipartUploadRequest;
use App\Http\Requests\Management\MediaMgmt\ListMediaMgmtRequest;
use App\Http\Requests\Management\MediaMgmt\StoreMediaMgmtRequest;
use App\Http\Requests\Management\MediaMgmt\UpdateMediaMgmtRequest;
use App\Http\Requests\Media\PrepareUploadRequest;
use App\Services\Management\MediaMgmtService;

class MediaMgmtController extends Controller
{
    public function __construct(
        protected MediaMgmtService $mediaMgmt
    ) {}

    /**
     * List media (files and folders)
     */
    public function list(ListMediaMgmtRequest $request): array
    {
        return $this->mediaMgmt->list($request->validated());
    }

    /**
     * Store media (file upload or folder creation)
     */
    public function store(StoreMediaMgmtRequest $request): int|array
    {
        return $this->mediaMgmt->store($request->validated());
    }

    /**
     * Update media (rename or move)
     */
    public function update(UpdateMediaMgmtRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = (int) $id;

        return $this->mediaMgmt->update($payload);
    }

    /**
     * Delete media (file or folder)
     */
    public function delete(DeleteMediaMgmtRequest $request, string $id): void
    {
        // Support both single delete (from route param) and batch delete (from request body)
        $ids = $request->has('ids') ? $request->validated()['ids'] : [(int) $id];
        $this->mediaMgmt->delete(['ids' => $ids]);
    }

    /**
     * Prepare a presigned URL for direct upload
     */
    public function prepareUpload(PrepareUploadRequest $request): array
    {
        return $this->mediaMgmt->prepareUpload($request->validated());
    }

    /**
     * Initialize Multipart Upload
     */
    public function initMultipartUpload(InitMultipartUploadRequest $request): array
    {
        return $this->mediaMgmt->initMultipartUpload($request->validated());
    }

    /**
     * Get Multipart Presigned URL
     */
    public function getMultipartUrl(GetMultipartUrlRequest $request): array
    {
        return $this->mediaMgmt->getMultipartPresignedUrl($request->validated());
    }

    /**
     * Complete Multipart Upload
     */
    public function completeMultipartUpload(CompleteMultipartUploadRequest $request): array
    {
        return $this->mediaMgmt->completeMultipartUpload($request->validated());
    }
}
