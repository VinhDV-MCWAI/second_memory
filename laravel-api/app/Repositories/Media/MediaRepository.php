<?php

declare(strict_types=1);

namespace App\Repositories\Media;

use App\Constants\MediaConst;
use App\Constants\Messages;
use App\Enums\UploadStatus;
use App\Models\Content\CategoryAttachment;
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

    /** Unaccented lower-case name: the expression of the `media_mgmt_name_trgm` index (SEARCH-01) */
    private const NAME_TEXT = 'f_unaccent(lower(media_mgmt.original_name))';

    /**
     * Direct children of a folder, folders first. Rows still uploading are not shown, nor folders in $blocked.
     *
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $blocked  paths of locked folders the admin may not use (ADR-0017)
     * @return LengthAwarePaginator<int, MediaMgmt>
     */
    public function list(string $folder, array $payload, array $blocked = []): LengthAwarePaginator
    {
        $query = $this->query()->live()->where('upload_status', '<>', UploadStatus::UPLOADING->value)
            ->with('departments:id');
        $this->whereChildOf($query, $folder);
        $this->excludeFolders($query, $blocked);

        if (isset($payload['name'])) {
            $query->whereRaw('lower(original_name) like lower(?)', ['%'.$this->escapeLike($payload['name']).'%']);
        }

        $query->orderBy('is_file');
        $this->applySorting($query, $payload, self::SORTABLE, 'original_name');

        return $query->paginate($payload['per_page'] ?? MediaConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }

    /**
     * Live files and folders for the admin search (SEARCH-01): every word inside the name, shortest
     * names first; with $fuzzy the closest names by trigram word similarity instead (the caller sets
     * `pg_trgm.word_similarity_threshold`). Unfinished uploads and everything in $blocked are left out.
     *
     * @param  list<string>  $words  letters and digits only, so nothing in them is special to LIKE
     * @param  list<string>  $blocked  paths of locked folders the admin may not use (ADR-0017)
     * @return Collection<int, MediaMgmt>
     */
    public function searchNames(array $words, bool $fuzzy, array $blocked, int $limit): Collection
    {
        $query = $this->query()->live()->where('upload_status', UploadStatus::COMPLETED->value);
        $this->excludeFolders($query, $blocked);

        if ($fuzzy) {
            $text = implode(' ', $words);
            $query->whereRaw('f_unaccent(lower(?)) <% '.self::NAME_TEXT, [$text])
                ->orderByRaw('f_unaccent(lower(?)) <<-> '.self::NAME_TEXT, [$text]);
        } else {
            foreach ($words as $word) {
                $query->whereRaw(self::NAME_TEXT." like '%' || f_unaccent(lower(?)) || '%'", [$word]);
            }
            $query->orderByRaw('length(media_mgmt.original_name)')->orderBy('media_mgmt.original_name');
        }

        return $query->limit($limit)->get();
    }

    /**
     * Paths of the locked folders (with a department_policy row) none of whose active departments the
     * admin belongs to (ADR-0017). Deleted folders count too when $withDeleted, for the trash.
     *
     * @return list<string>
     */
    public function foldersLockedAgainst(int $adminId, bool $withDeleted = false): array
    {
        $memberOf = DB::table('admin_department')
            ->join('department', 'department.id', '=', 'admin_department.department_id')
            ->where('admin_department.admin_id', $adminId)
            ->where('department.is_active', true)
            ->select('admin_department.department_id');

        return DB::table('department_policy')
            ->join('media_mgmt', 'media_mgmt.id', '=', 'department_policy.media_id')
            ->when(! $withDeleted, fn (QueryBuilder $q) => $q->where('media_mgmt.is_delete', false))
            ->groupBy('media_mgmt.id', 'media_mgmt.virtual_path')
            ->havingRaw('not bool_or(department_policy.department_id in ('.$memberOf->toSql().'))', $memberOf->getBindings())
            ->pluck('media_mgmt.virtual_path')
            ->map(strval(...))
            ->all();
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

    /**
     * Live completed files with one of $extensions whose duration and thumbnail were never looked for.
     *
     * @param  list<string>  $extensions
     * @return LazyCollection<int, MediaMgmt>
     */
    public function filesWithoutInfo(array $extensions): LazyCollection
    {
        return $this->query()->live()
            ->where('is_file', true)
            ->where('upload_status', UploadStatus::COMPLETED)
            ->whereRaw('coalesce((metadata->>?)::boolean, false) = false', [MediaConst::META_INFO_READ])
            ->whereIn(DB::raw('lower(extension)'), $extensions)
            ->lazyById();
    }

    /**
     * Stores what MediaInfoService found, without touching updated_at.
     *
     * @param  array<string, mixed>  $metadata  merged into the existing metadata
     */
    public function setInfo(MediaMgmt $file, ?int $duration, ?int $width, ?int $height, array $metadata): void
    {
        $values = ['metadata' => json_encode([...($file->metadata ?? []), ...$metadata], JSON_THROW_ON_ERROR)];
        if ($duration !== null) {
            $values['duration'] = $duration;
        }
        // An image's size comes from its header (MEDIA-10); ffprobe adds it for videos
        if ($width !== null && $height !== null && $file->width === null) {
            $values['width'] = $width;
            $values['height'] = $height;
        }
        $this->query()->whereKey($file->id)->toBase()->update($values);
    }

    /** Sets the size without touching updated_at (it is when the file last changed, or was deleted). */
    public function setImageSize(int $id, int $width, int $height): void
    {
        $this->query()->whereKey($id)->toBase()->update(['width' => $width, 'height' => $height]);
    }

    /**
     * The ids among $ids that are available files (what an article or a category may attach, ADR-0022).
     *
     * @param  list<int>  $ids
     * @param  list<string>  $blocked  paths of locked folders the admin may not use (ADR-0017)
     * @return list<int>
     */
    public function availableIds(array $ids, array $blocked = []): array
    {
        $query = $this->query()->available()->whereKey($ids);
        $this->excludeFolders($query, $blocked);

        return $query->pluck('id')->map(intval(...))->all();
    }

    /**
     * The available file behind a public key of the public site: an attachment of a category the
     * public site shows (ADR-0022); null otherwise.
     */
    public function publicFile(string $key): ?MediaMgmt
    {
        $attachment = CategoryAttachment::query()
            ->where('public_id', $key)
            ->whereHas('category', fn (Builder $category) => $category->visible())
            ->first();

        return $attachment?->media_id === null ? null : $this->query()->available()->find($attachment->media_id);
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
     * @param  list<string>  $blocked  paths of locked folders the admin may not use (ADR-0017)
     * @return LengthAwarePaginator<int, MediaMgmt>
     */
    public function trash(CarbonInterface $since, array $payload, array $blocked = []): LengthAwarePaginator
    {
        $query = $this->trashQuery($since)->with('updater:id,user_name');
        $this->excludeFolders($query, $blocked);
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
     * @param  list<string>  $blocked  paths of locked folders the admin may not use (ADR-0017)
     * @return Collection<int, MediaMgmt>
     */
    public function inTrash(CarbonInterface $since, ?array $ids = null, array $blocked = []): Collection
    {
        $query = $this->trashQuery($since)->when($ids !== null, fn (Builder $q) => $q->whereKey($ids));
        $this->excludeFolders($query, $blocked);

        return $query->get();
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

    /**
     * Leaves out the folders in $folders and everything below them.
     *
     * @param  Builder<MediaMgmt>  $query
     * @param  list<string>  $folders
     */
    private function excludeFolders(Builder $query, array $folders): void
    {
        foreach ($folders as $folder) {
            $query->where('media_mgmt.virtual_path', '<>', $folder)
                ->where('media_mgmt.virtual_path', 'not like', $this->escapeLike($folder).MediaConst::PATH_SEPARATOR.'%');
        }
    }

    /** `%`, `_` and `\` in a name are literal in LIKE patterns (PostgreSQL's default escape is `\`). */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
