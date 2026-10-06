<?php

namespace App\Services;

use App\Constants\MediaConst;
use Aws\S3\S3Client;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MinioService
{
    /**
     * Upload file to MinIO
     *
     * @param  string  $disk  One of MediaConst::DISK_*
     * @param  string  $prefix
     *
     * @throws Exception
     */
    public function upload(UploadedFile $file, string $disk, ?int $workspaceId = null): array
    {
        $storagePath = $this->generateStoragePath($file->getClientOriginalExtension(), $disk, $workspaceId);

        try {
            $result = Storage::disk($disk)->put($storagePath, fopen($file->getRealPath(), 'r'), [
                'Metadata' => [
                    'original-name' => $file->getClientOriginalName(),
                ],
            ]);

            if (! $result) {
                throw new Exception(__('minio.upload_file_to_storage_failed'));
            }

            $filesystem = Storage::disk($disk);
            /** @var FilesystemAdapter $filesystem */

            return [
                'disk' => $disk,
                'storage_path' => $storagePath,
                'object_key' => $storagePath,
                'url' => $filesystem->url($storagePath),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ];
        } catch (Exception $e) {
            Log::error(__('minio.upload_failed'), ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Move file in MinIO (Copy + Delete)
     */
    /**
     * Move file in MinIO (Copy + Delete) using internal S3 CopyObject
     */
    public function move(string $sourceDisk, string $sourceKey, string $destDisk, string $destKey, int $fileSize = 0): bool
    {
        try {
            // Use optimized copy if file size provided
            $copySuccess = $fileSize > 0
              ? $this->copyKeyOptimized($sourceDisk, $sourceKey, $destDisk, $destKey, $fileSize)
              : $this->copyKey($sourceDisk, $sourceKey, $destDisk, $destKey);

            if ($copySuccess) {
                return $this->delete($sourceDisk, $sourceKey);
            }

            return false;
        } catch (Exception $e) {
            Log::error(__('minio.move_failed'), ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Calculate optimal part size based on file size
     */
    private function calculatePartSize(int $fileSize): int
    {
        $MB = 1024 * 1024;
        $GB = 1024 * $MB;

        if ($fileSize < 500 * $MB) {
            return 16 * $MB; // 16MB
        } elseif ($fileSize < 10 * $GB) {
            return 32 * $MB; // 32MB
        } elseif ($fileSize < 100 * $GB) {
            return 64 * $MB; // 64MB
        } else {
            return max(128 * $MB, (int) ceil($fileSize / 10000)); // 128MB or fileSize/10000
        }
    }

    /**
     * Calculate optimal concurrency based on system resources
     */
    private function calculateConcurrency(): int
    {
        // Get CPU cores (fallback to 4 if can't detect)
        $cpuCores = (int) shell_exec('nproc 2>/dev/null') ?: 4;

        // Use 60% of available cores for copying
        $optimalConcurrency = (int) ceil($cpuCores * 0.6);

        // Cap between 5-12 for safety
        return max(5, min(12, $optimalConcurrency));
    }

    /**
     * Optimized copy with dynamic configuration
     */
    public function copyKeyOptimized(
        string $sourceDisk,
        string $sourceKey,
        string $destDisk,
        string $destKey,
        int $fileSize
    ): bool {
        try {
            $client = $this->getS3Client($destDisk, true);
            $destBucket = config("filesystems.disks.{$destDisk}.bucket");
            $sourceBucket = config("filesystems.disks.{$sourceDisk}.bucket");

            $partSize = $this->calculatePartSize($fileSize);
            $concurrency = $this->calculateConcurrency();

            Log::info('[MinIO] Copy config', [
                'file_size' => $fileSize,
                'part_size' => $partSize,
                'concurrency' => $concurrency,
                'source' => "{$sourceBucket}/{$sourceKey}",
                'dest' => "{$destBucket}/{$destKey}",
            ]);

            $startTime = microtime(true);

            // Use optimized copy with dynamic configuration
            // ACL must be passed as 5th parameter (string), options as 6th parameter (array)
            $client->copy($sourceBucket, $sourceKey, $destBucket, $destKey, 'private', [
                'params' => [
                    'StorageClass' => 'STANDARD',
                ],
                'concurrency' => $concurrency,
                'part_size' => $partSize,
            ]);

            $duration = microtime(true) - $startTime;
            $throughput = $duration > 0 ? ($fileSize / $duration) : 0;

            Log::info('[MinIO] Copy completed', [
                'duration' => round($duration, 2).'s',
                'throughput' => round($throughput / 1024 / 1024, 2).' MB/s',
            ]);

            return true;
        } catch (Exception $e) {
            Log::error(__('minio.copy_failed'), ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Copy file using S3 CopyObject (internal server-side copy)
     */
    public function copyKey(string $sourceDisk, string $sourceKey, string $destDisk, string $destKey): bool
    {
        try {
            $client = $this->getS3Client($destDisk, true);
            $destBucket = config("filesystems.disks.{$destDisk}.bucket");
            $sourceBucket = config("filesystems.disks.{$sourceDisk}.bucket");

            // Use high-level copy helper to handle files > 5GB (Multipart Copy)
            // This automatically handles multipart copying for large files
            $client->copy(
                $sourceBucket,
                $sourceKey,
                $destBucket,
                $destKey
            );

            return true;
        } catch (Exception $e) {
            Log::error(__('minio.copy_failed'), ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Delete file from MinIO
     */
    public function delete(string $disk, string $objectKey): bool
    {
        try {
            $client = $this->getS3Client($disk, true);
            $bucket = config("filesystems.disks.{$disk}.bucket");

            $client->deleteObject([
                'Bucket' => $bucket,
                'Key' => $objectKey,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error(__('minio.delete_failed'), ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Get public URL
     */
    public function getPublicUrl(string $disk, string $objectKey): string
    {
        /** @var FilesystemAdapter $filesystem */
        $filesystem = Storage::disk($disk);

        return $filesystem->url($objectKey);
    }

    /**
     * Generate storage path
     * Standard: {workspace}/{year}/{month}/{uuid}.{ext}
     * Temp: {uuid}.{ext}
     */
    public function generateStoragePath(string $extension, string $disk, ?int $workspaceId = null): string
    {
        $uuid = Str::uuid();

        if ($disk === MediaConst::DISK_TEMP) {
            return str_replace(
                ['{uuid}', '{extension}'],
                [$uuid, $extension],
                MediaConst::STORAGE_PATH_TEMP
            );
        }

        $year = date('Y');
        $month = date('m');
        $workspace = $workspaceId ? "workspace-{$workspaceId}" : 'default';

        return str_replace(
            ['{workspace}', '{year}', '{month}', '{uuid}', '{extension}'],
            [$workspace, $year, $month, $uuid, $extension],
            MediaConst::STORAGE_PATH_STANDARD
        );
    }

    /**
     * Generate Presigned Upload URL (PUT)
     *
     * @param  int  $expirySeconds  TTL in seconds
     */
    public function generatePresignedUploadUrl(string $extension, string $disk = MediaConst::DISK_TEMP, int $expirySeconds = MediaConst::PRESIGNED_UPLOAD_DEFAULT_EXPIRY): array
    {
        $key = $this->generateStoragePath($extension, $disk);

        $client = $this->getS3Client($disk, false);
        $bucket = config("filesystems.disks.{$disk}.bucket");

        // Create the command
        $cmd = $client->getCommand('PutObject', [
            'Bucket' => $bucket,
            'Key' => $key,
            'ACL' => 'private',
        ]);

        // Create the presigned request
        $request = $client->createPresignedRequest($cmd, "+{$expirySeconds} seconds");

        return [
            'upload_url' => (string) $request->getUri(),
            'method' => 'PUT',
            'key' => $key,
            'uuid' => basename($key, ".{$extension}"), // Extract UUID from key (temp path is {uuid}.ext)
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'expires_in' => $expirySeconds,
        ];
    }

    /**
     * Check if file exists
     */
    public function exists(string $disk, string $objectKey): bool
    {
        try {
            $client = $this->getS3Client($disk, true);
            $bucket = config("filesystems.disks.{$disk}.bucket");

            return $client->doesObjectExist($bucket, $objectKey);
        } catch (Exception $e) {
            Log::error(__('minio.exists_failed'), ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Create Multipart Upload
     */
    public function createMultipartUpload(string $extension, string $disk = MediaConst::DISK_TEMP): array
    {
        $key = $this->generateStoragePath($extension, $disk);
        $client = $this->getS3Client($disk, true);
        $bucket = config("filesystems.disks.{$disk}.bucket");

        $result = $client->createMultipartUpload([
            'Bucket' => $bucket,
            'Key' => $key,
            'ACL' => 'private',
            'ContentType' => 'application/octet-stream', // Important for parts
        ]);

        return [
            'upload_id' => $result['UploadId'],
            'key' => $key,
            'uuid' => basename($key, ".{$extension}"),
        ];
    }

    /**
     * Generate Presigned URL for Upload Part
     */
    public function generatePresignedUploadPartUrl(
        string $disk,
        string $key,
        string $uploadId,
        int $partNumber,
        int $expirySeconds = MediaConst::PRESIGNED_UPLOAD_DEFAULT_EXPIRY
    ): string {
        $client = $this->getS3Client($disk, false);
        $bucket = config("filesystems.disks.{$disk}.bucket");

        $cmd = $client->getCommand('UploadPart', [
            'Bucket' => $bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber,
        ]);

        $request = $client->createPresignedRequest($cmd, "+{$expirySeconds} seconds");

        return (string) $request->getUri();
    }

    /**
     * Complete Multipart Upload
     */
    public function completeMultipartUpload(
        string $disk,
        string $key,
        string $uploadId,
        array $parts
    ): void {
        $client = $this->getS3Client($disk, true);
        $bucket = config("filesystems.disks.{$disk}.bucket");

        $client->completeMultipartUpload([
            'Bucket' => $bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => $parts,
            ],
        ]);
    }

    /**
     * Abort Multipart Upload
     */
    public function abortMultipartUpload(string $disk, string $key, string $uploadId): void
    {
        $client = $this->getS3Client($disk, true);
        $bucket = config("filesystems.disks.{$disk}.bucket");

        $client->abortMultipartUpload([
            'Bucket' => $bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
        ]);
    }

    /**
     * Get configured S3 Client
     */
    private function getS3Client(string $disk, bool $useInternalEndpoint = false): S3Client
    {
        $endpoint = $useInternalEndpoint
          ? config("filesystems.disks.{$disk}.endpoint")
          : config('filesystems.disks.s3.url');

        $config = [
            'region' => config("filesystems.disks.{$disk}.region"),
            'version' => 'latest',
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => config("filesystems.disks.{$disk}.key"),
                'secret' => config("filesystems.disks.{$disk}.secret"),
            ],
        ];

        return new S3Client($config);
    }
}
