<?php

declare(strict_types=1);

namespace App\Models\Media;

use App\Enums\UploadStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
 * @property string|null $minio_bucket
 * @property string|null $minio_object_key
 * @property string|null $minio_etag
 * @property string|null $upload_id
 * @property UploadStatus $upload_status
 * @property bool $is_delete
 * @property int|null $created_by
 * @property int|null $updated_by
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
     * @param  Builder<MediaMgmt>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('media_mgmt.is_delete', false);
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'is_file' => 'boolean',
            'size' => 'integer',
            'upload_status' => UploadStatus::class,
            'is_delete' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }
}
