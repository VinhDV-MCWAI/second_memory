<?php

declare(strict_types=1);

namespace App\Models\Media;

use App\Enums\UploadStatus;
use App\Models\Ledger\Evidence;
use App\Models\Master\AdminMst;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * A file or a folder of the file manager (ADR-0014). Folders exist only here; a file's bytes live in
 * MinIO under `minio_object_key`. `is_delete` is a boolean in this table (not the 0/1 of HasSoftDelete).
 *
 * @property int $id
 * @property bool $is_file
 * @property string $virtual_path
 * @property string $original_name
 * @property string|null $extension
 * @property string|null $mime_type
 * @property int|null $size
 * @property int|null $width
 * @property int|null $height
 * @property string|null $minio_bucket
 * @property string|null $minio_object_key
 * @property string|null $minio_etag
 * @property string|null $upload_id
 * @property UploadStatus $upload_status
 * @property bool $is_delete
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $updated_at
 * @property-read AdminMst|null $updater
 * @property-read Collection<int, Evidence> $evidence
 * @property-read Pivot|null $pivot set when loaded through Evidence::files() (`public_id`, LEDGER-01)
 */
class MediaMgmt extends Model
{
    use HasFactory;

    protected $table = 'media_mgmt';

    protected $fillable = [
        'is_file',
        'virtual_path',
        'original_name',
        'extension',
        'mime_type',
        'size',
        'width',
        'height',
        'minio_bucket',
        'minio_object_key',
        'minio_etag',
        'upload_id',
        'upload_status',
        'is_delete',
        'created_by',
        'updated_by',
    ];

    /**
     * Last admin who changed the row; for a deleted row, who deleted it.
     *
     * @return BelongsTo<AdminMst, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(AdminMst::class, 'updated_by');
    }

    /**
     * Evidence this file is attached to (LEDGER-01).
     *
     * @return BelongsToMany<Evidence, $this>
     */
    public function evidence(): BelongsToMany
    {
        return $this->belongsToMany(Evidence::class, 'evidence_media', 'media_id', 'evidence_id');
    }

    /**
     * @param  Builder<MediaMgmt>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('media_mgmt.is_delete', false);
    }

    /**
     * Files that can be shown or downloaded: not deleted and fully uploaded.
     *
     * @param  Builder<MediaMgmt>  $query
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->live()->where('media_mgmt.is_file', true)->where('media_mgmt.upload_status', UploadStatus::COMPLETED->value);
    }

    public function isAvailable(): bool
    {
        return ! $this->is_delete && $this->is_file && $this->upload_status === UploadStatus::COMPLETED;
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'is_file' => 'boolean',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'upload_status' => UploadStatus::class,
            'is_delete' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }
}
