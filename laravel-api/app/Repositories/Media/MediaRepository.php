<?php

declare(strict_types=1);

namespace App\Repositories\Media;

use App\Constants\MediaConst;
use App\Enums\UploadStatus;
use App\Models\Media\MediaMgmt;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Folders are rows too; the tree is the `virtual_path` column (`/Docs/report.pdf`).
 */
class MediaRepository extends BaseRepository
{
    private const SORTABLE = ['original_name', 'size', 'created_at', 'updated_at'];

    public function __construct(MediaMgmt $model)
    {
        parent::__construct($model);
    }

    /**
     * Direct children of a folder, folders first. Rows still uploading are not shown.
     *
     * @param  array<string, mixed>  $payload
     * @return LengthAwarePaginator<int, MediaMgmt>
     */
    public function list(string $folder, array $payload): LengthAwarePaginator
    {
        $query = $this->query()->live()->where('upload_status', '<>', UploadStatus::UPLOADING->value);
        $this->whereChildOf($query, $folder);

        if (isset($payload['name'])) {
            $query->whereRaw('lower(original_name) like lower(?)', ['%'.$this->escapeLike($payload['name']).'%']);
        }

        $query->orderBy('is_file');
        $this->applySorting($query, $payload, self::SORTABLE, 'original_name');

        return $query->paginate($payload['per_page'] ?? MediaConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }

    public function findLive(int $id): ?MediaMgmt
    {
        return $this->query()->live()->find($id);
    }

    public function folderExists(string $path): bool
    {
        return $path === MediaConst::ROOT
            || $this->query()->live()->where('is_file', false)->where('virtual_path', $path)->exists();
    }

    /** A live, finished item (file or folder) holds this path, ignoring case. */
    public function pathTaken(string $path, ?int $exceptId = null): bool
    {
        return $this->query()->live()
            ->where('upload_status', '<>', UploadStatus::UPLOADING->value)
            ->whereRaw('lower(virtual_path) = lower(?)', [$path])
            ->when($exceptId !== null, fn (Builder $q) => $q->whereKeyNot($exceptId))
            ->exists();
    }

    /**
     * Every live row below a folder, at any depth.
     *
     * @return Collection<int, MediaMgmt>
     */
    public function descendants(string $folder): Collection
    {
        return $this->query()->live()
            ->where('virtual_path', 'like', $this->escapeLike($folder).MediaConst::PATH_SEPARATOR.'%')
            ->get();
    }

    /** Re-roots the live rows below $from under $to (folder rename or move). */
    public function moveDescendants(string $from, string $to): void
    {
        $this->query()->live()
            ->where('virtual_path', 'like', $this->escapeLike($from).MediaConst::PATH_SEPARATOR.'%')
            // substr() counts characters, like mb_strlen()
            ->update(['virtual_path' => DB::raw(DB::getPdo()->quote($to).' || substr(virtual_path, '.(mb_strlen($from) + 1).')')]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): MediaMgmt
    {
        return $this->query()->create($attributes);
    }

    /**
     * @param  list<int>  $ids
     */
    public function markDeleted(array $ids, ?int $actorId): void
    {
        $this->query()->whereKey($ids)->update(['is_delete' => true, 'updated_by' => $actorId, 'updated_at' => now()]);
    }

    /**
     * @return Builder<MediaMgmt>
     */
    private function query(): Builder
    {
        /** @var Builder<MediaMgmt> */
        return $this->model->newQuery();
    }

    /**
     * @param  Builder<MediaMgmt>  $query
     */
    private function whereChildOf(Builder $query, string $folder): void
    {
        $prefix = $folder === MediaConst::ROOT ? '' : $this->escapeLike($folder);
        $query->where('virtual_path', 'like', $prefix.MediaConst::PATH_SEPARATOR.'%')
            ->where('virtual_path', 'not like', $prefix.MediaConst::PATH_SEPARATOR.'%'.MediaConst::PATH_SEPARATOR.'%');
    }

    /** `%`, `_` and `\` in a name are literal in LIKE patterns (PostgreSQL's default escape is `\`). */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
