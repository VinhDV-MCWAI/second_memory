<?php

namespace App\Models\Management;

use App\Enums\UploadStatus;
use App\Traits\HasSoftDelete;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class MediaMgmt extends Model
{
    use HasSoftDelete;

    protected $table = 'media_mgmt';

    protected $fillable = [
        'workspace_id',
        'is_file',
        'virtual_path',
        'storage_path',
        'original_name',
        'extension',
        'mime_type',
        'size',
        'minio_bucket',
        'minio_object_key',
        'minio_etag',
        'url',
        'width',
        'height',
        'duration',
        'metadata',
        'is_delete',
        'created_by',
        'updated_by',
        'upload_status',
    ];

    /**
     * Attributes to append to model's array/JSON form
     */
    protected $appends = [
        'folder_path',
    ];

    /**
     * Check if file (not folder)
     */
    public function isFile(): bool
    {
        return $this->is_file;
    }

    /**
     * Check if folder
     */
    public function isFolder(): bool
    {
        return ! $this->is_file;
    }

    /**
     * Get parent folder path
     */
    public function getParentPath(): string
    {
        return dirname($this->virtual_path);
    }

    /**
     * Get children (for folders)
     */
    public function children()
    {
        if ($this->isFile()) {
            return collect([]);
        }

        $childrenPath = rtrim($this->virtual_path, '/').'/';

        return self::where('virtual_path', 'LIKE', $childrenPath.'%')
            ->notDeleted()
            ->get();
    }

    /**
     * Get folder path (parent directory of this file/folder)
     * This accessor is used by MediaFileResource to return folder_path
     */
    protected function folderPath(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->is_file) {
                // For files, return the directory containing the file
                return dirname($this->virtual_path);
            } else {
                // For folders, return the parent folder
                // Remove trailing slash first
                $path = rtrim($this->virtual_path, '/');

                return dirname($path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_file' => 'boolean',
            'is_delete' => 'boolean',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration' => 'integer',
            'metadata' => 'array',
            'upload_status' => UploadStatus::class,
        ];
    }
}
