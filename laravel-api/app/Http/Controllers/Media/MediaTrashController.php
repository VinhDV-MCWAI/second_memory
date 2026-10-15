<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\DeleteMediaRequest;
use App\Http\Requests\Media\ListTrashRequest;
use App\Http\Resources\Media\MediaResource;
use App\Http\Resources\Media\TrashItemResource;
use App\Services\Media\MediaTrashService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Trash of the file manager: deleted files are kept 30 days, then MinIO drops them.
 */
class MediaTrashController extends Controller
{
    public function __construct(private readonly MediaTrashService $trash) {}

    /**
     * Deleted files still kept, most recently deleted first
     *
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, TrashItemResource>>
     */
    public function list(ListTrashRequest $request): AnonymousResourceCollection
    {
        return TrashItemResource::collection($this->trash->list($request->validated()));
    }

    /**
     * Restore a deleted file to its folder (recreated if needed); a taken name becomes `name (restored).ext`
     */
    public function restore(string $id): MediaResource
    {
        return new MediaResource($this->trash->restore((int) $id));
    }

    /**
     * Delete files in the trash for good; returns how many
     */
    public function delete(DeleteMediaRequest $request): int
    {
        return $this->trash->purge($request->validated('ids'));
    }

    /**
     * Delete everything in the trash for good; returns how many files
     */
    public function empty(): int
    {
        return $this->trash->purge(null);
    }
}
