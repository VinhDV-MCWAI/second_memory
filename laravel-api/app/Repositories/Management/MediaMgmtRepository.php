<?php

declare(strict_types=1);

namespace App\Repositories\Management;

use App\Enums\IsDelete;
use App\Enums\UploadStatus;
use App\Interfaces\Management\MediaMgmtInterface;
use App\Models\Management\MediaMgmt;
use App\Repositories\BaseRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class MediaMgmtRepository extends BaseRepository implements MediaMgmtInterface
{
    public function __construct(MediaMgmt $model)
    {
        parent::__construct($model);
    }

    /**
     * Get list of files/folders (file manager - no pagination)
     */
    public function list(array $payload): Collection
    {
        $query = $this->model->query()
            ->notDeleted();

        // If ID is provided, filter by ID and return specific item (ignoring folder structure)
        if (isset($payload['id'])) {
            $query->where('id', $payload['id']);

            return $query->get();
        }

        // Filter by folder (virtual_path parent)
        // Support both 'parent_path' and 'folder_path' for backward compatibility
        // Default to root level if no folder parameter provided
        $parentPath = $payload['parent_path'] ?? $payload['folder_path'] ?? '/';
        $parentPath = rtrim($parentPath, '/');

        if ($parentPath === '' || $parentPath === '/') {
            // Root level - only items directly under root (no additional slashes)
            $query->where(function ($q) {
                $q->whereRaw("virtual_path ~ '^/[^/]+/?$'");
            });
        } else {
            // Get only direct children of the parent folder
            $parentPath = $parentPath.'/';
            $query->where('virtual_path', 'LIKE', $parentPath.'%')
                ->whereRaw('virtual_path ~ ?', ['^'.preg_quote($parentPath, '/').'[^/]+/?$']);
        }

        // Filter by type (file/folder)
        if (isset($payload['is_file'])) {
            $query->where('is_file', $payload['is_file']);
        }

        // Filter by upload status (default: only show completed files)
        if (isset($payload['upload_status'])) {
            if (is_array($payload['upload_status'])) {
                $query->whereIn('upload_status', $payload['upload_status']);
            } else {
                $query->where('upload_status', $payload['upload_status']);
            }
        } else {
            // Default: only show completed files (or folders which don't have upload_status)
            $query->where(function ($q) {
                $q->where('upload_status', UploadStatus::COMPLETED->value)
                    ->orWhere('is_file', false); // Folders don't have upload status
            });
        }

        // Filter by MIME type
        if (isset($payload['mime_type'])) {
            $query->where('mime_type', 'LIKE', $payload['mime_type'].'%');
        }

        // Search by name
        if (isset($payload['search'])) {
            $query->where('original_name', 'LIKE', '%'.$payload['search'].'%');
        }

        // Apply sorting
        $this->applySorting($query, $payload, 'id', 'desc');

        // File manager: return all files without pagination
        return $query->get();
    }

    /**
     * Find by ID
     */
    public function find(int $id)
    {
        return $this->model->find($id);
    }

    /**
     * Create new record
     */
    public function executeStore(array $payload): int
    {
        $model = $this->model->newInstance()->fill(
            Arr::only($payload, $this->model->getFillable())
        );

        $model->save();

        return $model->id;
    }

    /**
     * Update record
     */
    public function executeUpdate(array $payload): int
    {
        $model = $this->model->findOrFail($payload['id']);

        if ($model->isDeleted()) {
            throw new \LogicException('Cannot update deleted record');
        }

        $model->fill(Arr::only($payload, $this->model->getFillable()));
        $model->save();

        return $model->id;
    }

    /**
     * Delete record (soft delete with is_delete flag)
     */
    public function executeDelete(array $ids): void
    {
        $this->model->whereIn('id', $ids)
            ->notDeleted()
            ->update(['is_delete' => IsDelete::TRUE->value]);
    }
}
