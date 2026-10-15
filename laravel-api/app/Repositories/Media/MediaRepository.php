<?php

declare(strict_types=1);

namespace App\Repositories\Media;

use App\Constants\MediaConst;
use App\Constants\Messages;
use App\Enums\UploadStatus;
use App\Models\Media\MediaMgmt;
use App\Repositories\BaseRepository;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Illuminate\Validation\ValidationException;

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
        $query = $this->query()->live()->where('upload_status', '<>', UploadStatus::UPLOADING->value)
            ->select('media_mgmt.*')
            ->with('evidence:id,title')
            ->selectSub($this->attachedCount(), 'attached_count');
        $this->whereChildOf($query, $folder);

        if (isset($payload['name'])) {
            $query->whereRaw('lower(original_name) like lower(?)', ['%'.$this->escapeLike($payload['name']).'%']);
        }

        $query->orderBy('is_file');
        $this->applySorting($query, $payload, self::SORTABLE, 'original_name');

        return $query->paginate($payload['per_page'] ?? MediaConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }

    /**
     * Links to evidence of the row: of the file itself, or of the live files inside the folder
     * (MEDIA-09). `\` escapes LIKE wildcards in the stored path, as escapeLike() does.
     */
    private function attachedCount(): QueryBuilder
    {
        return DB::table('evidence_media')
            ->join('media_mgmt as attached', 'attached.id', '=', 'evidence_media.media_id')
            ->where('attached.is_delete', false)
            ->where(fn (QueryBuilder $match) => $match
                ->whereColumn('attached.id', 'media_mgmt.id')
                ->orWhereRaw("attached.virtual_path like replace(replace(replace(media_mgmt.virtual_path, '\\', '\\\\'), '%', '\\%'), '_', '\\_') || '/%'"))
            ->selectRaw('count(*)');
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
     *
     * @throws ValidationException when the unique index refuses the path
     */
    public function create(array $attributes): MediaMgmt
    {
        return $this->unique(fn (): MediaMgmt => $this->query()->create($attributes));
    }

    /** @throws ValidationException when the unique index refuses the path */
    public function save(MediaMgmt $item): void
    {
        $this->unique(fn (): bool => $item->save());
    }

    /**
     * Live completed files with one of $extensions and no width yet.
     *
     * @param  list<string>  $extensions
     * @return LazyCollection<int, MediaMgmt>
     */
    public function imagesWithoutSize(array $extensions): LazyCollection
    {
        return $this->query()->live()
            ->where('is_file', true)
            ->where('upload_status', UploadStatus::COMPLETED)
            ->whereNull('width')
            ->whereIn(DB::raw('lower(extension)'), $extensions)
            ->lazyById();
    }

    /** Sets the size without touching updated_at (it is when the file last changed, or was deleted). */
    public function setImageSize(int $id, int $width, int $height): void
    {
        $this->query()->whereKey($id)->toBase()->update(['width' => $width, 'height' => $height]);
    }

    /**
     * The ids among $ids that are available files (LEDGER-01: what an evidence may attach).
     *
     * @param  list<int>  $ids
     * @return list<int>
     */
    public function availableIds(array $ids): array
    {
        return $this->query()->available()->whereKey($ids)->pluck('id')->map(intval(...))->all();
    }

    /**
     * The available file of an attachment (`evidence_media.public_id`) to public evidence of a public
     * skill, the only files the public Skill Ledger serves (LEDGER-01); null otherwise.
     */
    public function publicFile(string $key): ?MediaMgmt
    {
        return $this->query()->available()
            ->whereHas('evidence', fn ($evidence) => $evidence
                ->where('evidence_media.public_id', $key)
                ->where('evidence.is_public', true)
                ->whereNull('evidence.unpublished_at')
                ->whereHas('skills', fn ($skills) => $skills->where('skill.is_public', true)))
            ->first();
    }

    /**
     * Unfinished uploads (still uploading or processing) last touched before $before.
     *
     * @return Collection<int, MediaMgmt>
     */
    public function stuckUploads(CarbonInterface $before): Collection
    {
        return $this->query()->live()
            ->where('is_file', true)
            ->whereIn('upload_status', [UploadStatus::UPLOADING->value, UploadStatus::PROCESSING->value])
            ->where('updated_at', '<', $before)
            ->get();
    }

    /**
     * Multipart uploads an admin started and has not finished, newest first.
     *
     * @return Collection<int, MediaMgmt>
     */
    public function unfinishedUploadsOf(int $adminId): Collection
    {
        return $this->query()->live()
            ->where('upload_status', UploadStatus::UPLOADING->value)
            ->whereNotNull('upload_id')
            ->where('created_by', $adminId)
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * @param  list<int>  $ids
     */
    public function markDeleted(array $ids, ?int $actorId): void
    {
        $this->query()->whereKey($ids)->update(['is_delete' => true, 'updated_by' => $actorId, 'updated_at' => now()]);
    }

    /**
     * Deleted files MinIO still keeps (deleted after $since), most recently deleted first. A deleted
     * row is never changed again, so `updated_at` is the deletion time and `updated_by` who deleted it.
     *
     * @param  array<string, mixed>  $payload
     * @return LengthAwarePaginator<int, MediaMgmt>
     */
    public function trash(CarbonInterface $since, array $payload): LengthAwarePaginator
    {
        $query = $this->trashQuery($since)->with('updater:id,user_name');
        if (isset($payload['name'])) {
            $query->whereRaw('lower(original_name) like lower(?)', ['%'.$this->escapeLike($payload['name']).'%']);
        }

        return $query->orderByDesc('updated_at')->orderByDesc('id')
            ->paginate($payload['per_page'] ?? MediaConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }

    /**
     * Trash rows among $ids, or every trash row when $ids is null.
     *
     * @param  list<int>|null  $ids
     * @return Collection<int, MediaMgmt>
     */
    public function inTrash(CarbonInterface $since, ?array $ids = null): Collection
    {
        return $this->trashQuery($since)->when($ids !== null, fn (Builder $q) => $q->whereKey($ids))->get();
    }

    /**
     * Every deleted row (file, folder, failed upload) deleted before $before: past the trash, MinIO no
     * longer keeps their bytes (MEDIA-08).
     *
     * @return Collection<int, MediaMgmt>
     */
    public function deletedBefore(CarbonInterface $before): Collection
    {
        return $this->query()->where('is_delete', true)->where('updated_at', '<', $before)->get();
    }

    /** Rows of files deleted for good: their history stays in audit_log. */
    public function forceDelete(Collection $rows): void
    {
        $this->query()->whereKey($rows->modelKeys())->delete();
    }

    /**
     * Two requests can pass pathTaken() together; the partial unique index decides, the loser gets a 422.
     *
     * @template T
     *
     * @param  callable(): T  $write
     * @return T
     */
    private function unique(callable $write): mixed
    {
        try {
            return $write();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => Messages::E0029]);
        }
    }

    /**
     * @return Builder<MediaMgmt>
     */
    private function trashQuery(CarbonInterface $since): Builder
    {
        return $this->query()
            ->where('is_delete', true)
            ->where('is_file', true)
            ->where('upload_status', UploadStatus::COMPLETED->value)
            ->where('updated_at', '>=', $since);
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
