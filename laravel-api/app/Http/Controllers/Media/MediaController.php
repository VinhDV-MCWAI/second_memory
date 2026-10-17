<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\CreateUploadRequest;
use App\Http\Requests\Media\DeleteMediaRequest;
use App\Http\Requests\Media\DownloadUrlRequest;
use App\Http\Requests\Media\ListMediaRequest;
use App\Http\Requests\Media\StoreFolderRequest;
use App\Http\Requests\Media\UpdateMediaAccessRequest;
use App\Http\Requests\Media\UpdateMediaRequest;
use App\Http\Resources\Media\MediaResource;
use App\Services\Media\MediaService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * File manager on MinIO (ADR-0014). Bytes never pass through PHP: the browser uploads and downloads
 * with presigned URLs.
 */
class MediaController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    /**
     * Direct children of a folder (default `/`), folders first
     *
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, MediaResource>>
     */
    public function list(ListMediaRequest $request): AnonymousResourceCollection
    {
        return MediaResource::collection($this->media->list($request->validated()));
    }

    /**
     * Create a folder
     */
    public function storeFolder(StoreFolderRequest $request): int
    {
        return $this->media->storeFolder($request->validated());
    }

    /**
     * Start a small-file upload: returns a presigned URL the browser PUTs the file to
     *
     * @return array{id: int, url: string, method: string, headers: array{'Content-Type': string}, expires_at: string}
     */
    public function uploadUrl(CreateUploadRequest $request): array
    {
        return $this->media->createUpload($request->validated());
    }

    /**
     * Finish an upload after the PUT succeeded; the file must be in MinIO with the declared size
     */
    public function complete(string $id): MediaResource
    {
        return new MediaResource($this->media->completeUpload((int) $id));
    }

    /**
     * Presigned download URL of a finished file; with `inline=1` a preview URL the browser shows itself (only when the file's `preview` is set)
     *
     * @return array{url: string, expires_at: string}
     */
    public function downloadUrl(DownloadUrlRequest $request, string $id): array
    {
        return $this->media->downloadUrl((int) $id, (bool) $request->validated('inline', false));
    }

    /**
     * Rename and/or move a file or folder
     */
    public function update(UpdateMediaRequest $request, string $id): int
    {
        return $this->media->update((int) $id, $request->validated());
    }

    /**
     * Give a folder to departments: only their members (and owners) may use it and everything inside; an empty list opens it
     */
    public function access(UpdateMediaAccessRequest $request, string $id): int
    {
        return $this->media->setAccess((int) $id, array_map(intval(...), $request->validated('department_ids')));
    }

    /**
     * Delete files and folders (a folder with everything inside)
     */
    public function delete(DeleteMediaRequest $request): void
    {
        $this->media->delete($request->validated());
    }
}
