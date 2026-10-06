<?php

namespace App\Services\Management;

use App\Constants\MediaConst;
use App\Http\Resources\Management\MediaFileResource;
use App\Interfaces\Management\MediaMgmtInterface;
use App\Services\BaseService;
use App\Services\MinioService;
use Exception;

class MediaMgmtService extends BaseService
{
    public function __construct(
        protected MediaMgmtInterface $mediaMgmt,
        protected MinioService $minioService
    ) {}

    protected function getHistoryRepository()
    {
        return null; // No history tracking for media
    }

    protected function getHistoryForeignKey(): string
    {
        return '';
    }

    /**
     * List media (files and folders)
     */
    public function list(array $payload): array
    {
        $list = $this->mediaMgmt->list($payload);

        // Manually transform to array to avoid ResourceCollection pagination wrapper
        return $list->map(function ($item) {
            return MediaFileResource::make($item)->resolve();
        })->values()->all();
    }

    /**
     * Store media (file upload or folder creation)
     * Returns int (folder ID) or array (file upload with room_id)
     */
    public function store(array $payload): int|array
    {
        // If key is provided, move from temp (returns array with room_id)
        if (isset($payload['key'])) {
            return $this->storeFromTemp($payload);
        }

        // Otherwise, create folder (returns int)
        return $this->createFolder($payload);
    }

    /**
     * Update media (rename or move)
     */
    public function update(array $payload): int
    {
        $media = $this->mediaMgmt->find($payload['id']);

        if (! $media) {
            throw new Exception(__('messages.media.not_found'));
        }

        // If new_parent_path is provided, it's a move operation
        if (isset($payload['new_parent_path'])) {
            return $this->move($payload, $media);
        }

        // Otherwise, it's a rename operation
        if (isset($payload['name'])) {
            return $this->rename($payload, $media);
        }

        throw new Exception(__('messages.media.name_or_parent_required'));
    }

    /**
     * Delete (file or folder)
     */
    public function delete(array $payload): void
    {
        $ids = $payload['ids'] ?? [];

        foreach ($ids as $id) {
            $media = $this->mediaMgmt->find($id);

            if ($media && $media->isFile() && $media->storage_path) {
                // Delete from MinIO
                $this->minioService->delete($media->minio_bucket, $media->storage_path);
            }
        }

        // Soft delete in DB
        $this->mediaMgmt->executeDelete($ids);
    }

    /**
     * Create folder (internal method)
     */
    protected function createFolder(array $payload): int
    {
        $parentPath = $payload['parent_path'] ?? '/';
        $folderName = $payload['name'];

        $virtualPath = rtrim($parentPath, '/').'/'.$folderName.'/';

        $data = [
            'workspace_id' => $payload['workspace_id'] ?? null,
            'is_file' => MediaConst::TYPE_FOLDER,
            'virtual_path' => $virtualPath,
            'storage_path' => null,  // Folders don't have storage path
            'original_name' => $folderName,
            'is_delete' => false,
        ];

        return $this->mediaMgmt->executeStore($data);
    }

    /**
     * Prepare upload (Generate Presigned URL)
     */
    public function prepareUpload(array $payload): array
    {
        $extension = $payload['extension'];
        $ttl = MediaConst::PRESIGNED_UPLOAD_TTL; // 5 seconds

        return $this->minioService->generatePresignedUploadUrl($extension, MediaConst::DISK_TEMP, $ttl);
    }

    /**
     * Initialize Multipart Upload
     */
    public function initMultipartUpload(array $payload): array
    {
        $size = $payload['size'];
        $extension = $payload['extension'];

        // Calculate Part Size
        // 100MB - 500MB: 16MB
        // 500MB - 10GB: 32MB
        // 10GB - 100GB: 64MB
        // >100GB: 128MB

        $partSize = MediaConst::MULTIPART_MIN_SIZE_16MB; // Default 16MB
        if ($size > MediaConst::SIZE_100GB) { // > 100GB
            $partSize = MediaConst::MULTIPART_MIN_SIZE_128MB;
        } elseif ($size > MediaConst::SIZE_10GB) { // > 10GB
            $partSize = MediaConst::MULTIPART_MIN_SIZE_64MB;
        } elseif ($size > MediaConst::SIZE_500MB) { // > 500MB
            $partSize = MediaConst::MULTIPART_MIN_SIZE_32MB;
        }

        // Ensure within 10000 parts limit
        $calculatedPartSize = ceil($size / MediaConst::MULTIPART_MAX_PARTS);
        $partSize = max($partSize, $calculatedPartSize);

        // Call Minio to create multipart upload
        $result = $this->minioService->createMultipartUpload($extension, MediaConst::DISK_TEMP);

        return [
            'upload_id' => $result['upload_id'],
            'key' => $result['key'],
            'part_size' => $partSize,
            'parts_count' => ceil($size / $partSize),
        ];
    }

    /**
     * Get Presigned URL for a Part
     */
    /**
     * Get Presigned URL for a Part
     */
    public function getMultipartPresignedUrl(array $payload): array
    {
        $key = $payload['key'];
        $uploadId = $payload['upload_id'];
        $partNumber = $payload['part_number'];
        $size = $payload['size'] ?? 0;

        // Calculate TTL based on file size
        if ($size <= MediaConst::SIZE_100MB) {
            $ttl = MediaConst::MULTIPART_TTL_SMALL; // 300s
        } elseif ($size <= MediaConst::SIZE_10GB) {
            $ttl = MediaConst::MULTIPART_TTL_MEDIUM; // 60s
        } elseif ($size <= MediaConst::SIZE_100GB) {
            $ttl = MediaConst::MULTIPART_TTL_LARGE; // 120s
        } else {
            $ttl = MediaConst::MULTIPART_TTL_LARGE; // 120s
        }

        $url = $this->minioService->generatePresignedUploadPartUrl(MediaConst::DISK_TEMP, $key, $uploadId, $partNumber, $ttl);

        return [
            'url' => $url,
            'part_number' => $partNumber,
            'expires_in' => $ttl,
        ];
    }

    /**
     * Complete Multipart Upload
     */
    public function completeMultipartUpload(array $payload): array
    {
        $key = $payload['key'];
        $uploadId = $payload['upload_id'];
        $parts = $payload['parts']; // Array of ['PartNumber' => x, 'ETag' => y]
        $originalName = $payload['original_name'];
        $extension = $payload['extension'];
        $workspaceId = $payload['workspace_id'] ?? null;
        $parentPath = $payload['parent_path'] ?? '/';
        $size = $payload['size'] ?? 0;

        // 1. Complete on Minio
        $formattedParts = [];
        foreach ($parts as $part) {
            $formattedParts[] = [
                'PartNumber' => $part['part_number'],
                'ETag' => $part['etag'],
            ];
        }

        $this->minioService->completeMultipartUpload(MediaConst::DISK_TEMP, $key, $uploadId, $formattedParts);

        // 2. Return success with temp file info
        return [
            'key' => $key,
            'original_name' => $originalName,
            'extension' => $extension,
            'mime_type' => $payload['mime_type'] ?? null,
            'size' => $size,
            'parent_path' => $parentPath,
            'workspace_id' => $workspaceId,
        ];
    }

    /**
     * Store file from Temp (Move to Official + Create DB Record)
     * Returns array with media_id, status, and optionally room_id for heavy files
     */
    public function storeFromTemp(array $payload): array
    {
        $tempKey = $payload['key']; // This is the path in temp bucket: {uuid}.{ext}
        $extension = $payload['extension'];
        $workspaceId = $payload['workspace_id'] ?? null;
        $parentPath = $payload['parent_path'] ?? '/';
        $originalName = $payload['original_name'];
        $fileSize = $payload['size'] ?? 0;

        // 1. Verify file exists in Temp (Optional but recommended)
        if (! $this->minioService->exists(MediaConst::DISK_TEMP, $tempKey)) {
            throw new Exception(__('messages.media.file_not_found_temp', ['key' => $tempKey]));
        }

        // 2. Generate new storage path for Official bucket
        // Format: {workspace}/{year}/{month}/{uuid}.{ext}
        $officialDisk = MediaConst::DISK_OFFICIAL;
        $officialPath = $this->minioService->generateStoragePath($extension, $officialDisk, $workspaceId);

        // 3. Create DB Record
        $virtualPath = rtrim($parentPath, '/').'/'.$originalName;

        // Check if file is heavy (>= 100MB) or light
        $isHeavyFile = $fileSize >= MediaConst::SIZE_100MB;

        // Get current user ID for audit
        $currentUserId = request()->attributes->get('current_admin_id');

        $data = [
            'workspace_id' => $workspaceId,
            'is_file' => MediaConst::TYPE_FILE,
            'virtual_path' => $virtualPath,
            'storage_path' => $officialPath,
            'original_name' => $originalName,
            'extension' => $extension,
            'mime_type' => $payload['mime_type'] ?? null,
            'size' => $fileSize,
            'minio_bucket' => $officialDisk,
            'minio_object_key' => $officialPath,
            'url' => $this->minioService->getPublicUrl($officialDisk, $officialPath),
            'is_delete' => false,
            'upload_status' => $isHeavyFile ? \App\Enums\UploadStatus::PROCESSING : \App\Enums\UploadStatus::COMPLETED,
            'created_by' => $currentUserId,
        ];

        $mediaId = $this->mediaMgmt->executeStore($data);
        $media = $this->mediaMgmt->find($mediaId);

        // 4. Process based on file size
        if ($isHeavyFile) {
            // Heavy file: Dispatch async job
            // Generate room ID for WebSocket notification
            // Format: {uuid}_{adminId}_upload_file
            $uuid = \Illuminate\Support\Str::uuid()->toString();
            $roomId = "{$uuid}_{$currentUserId}_upload_file";

            // Dispatch Job to Process File (Move + Notify)
            \App\Jobs\Media\ProcessLargeFile::dispatch($media, $tempKey, $roomId);

            return [
                'media_id' => $media->id,
                'room_id' => $roomId,
                'status' => \App\Enums\UploadStatus::PROCESSING->value,
                'message' => 'File is being processed. You will be notified when it is ready.',
            ];
        } else {
            // Light file: Move synchronously
            try {
                // Move from temp to official bucket
                $moveSuccess = $this->minioService->move(
                    MediaConst::DISK_TEMP,
                    $tempKey,
                    MediaConst::DISK_OFFICIAL,
                    $officialPath,
                    $fileSize
                );

                if (! $moveSuccess) {
                    throw new Exception('Failed to move file from temp to official bucket');
                }

                return [
                    'media_id' => $media->id,
                    'room_id' => null,
                    'status' => \App\Enums\UploadStatus::COMPLETED->value,
                    'message' => 'File uploaded successfully.',
                ];
            } catch (\Exception $e) {
                // If move fails, mark as failed and delete DB record
                $this->mediaMgmt->executeDelete([$mediaId]);
                throw new Exception(__('messages.media.move_file_failed').': '.$e->getMessage());
            }
        }
    }

    /**
     * Rename (internal method)
     */
    protected function rename(array $payload, $media): int
    {
        $newName = $payload['name'];
        $parentPath = dirname($media->virtual_path);
        $newVirtualPath = rtrim($parentPath, '/').'/'.$newName;

        if (! $media->isFile()) {
            $newVirtualPath .= '/';
        }

        $updateData = [
            'id' => $media->id,
            'original_name' => $newName,
            'virtual_path' => $newVirtualPath,
        ];

        return $this->mediaMgmt->executeUpdate($updateData);
    }

    /**
     * Move (internal method)
     */
    protected function move(array $payload, $media): int
    {
        $newParentPath = $payload['new_parent_path'] ?? '/';
        $currentVirtualPath = $media->virtual_path;

        // Build new virtual path
        $newVirtualPath = rtrim($newParentPath, '/').'/'.$media->original_name;

        if (! $media->isFile()) {
            $newVirtualPath .= '/';

            // IMPORTANT: Prevent moving folder into itself or its own subfolder
            if (str_starts_with($newVirtualPath, $currentVirtualPath)) {
                throw new Exception(__('messages.media.move_folder_recursion'));
            }
        }

        // Additional check: if new path equals current path, no need to move
        if ($newVirtualPath === $currentVirtualPath) {
            return $media->id; // Return same ID, no changes needed
        }

        $updateData = [
            'id' => $media->id,
            'virtual_path' => $newVirtualPath,
        ];

        return $this->mediaMgmt->executeUpdate($updateData);
    }
}
