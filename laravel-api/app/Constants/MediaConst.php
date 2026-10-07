<?php

declare(strict_types=1);

namespace App\Constants;

class MediaConst
{
    // File types
    public const TYPE_FILE = true;

    public const TYPE_FOLDER = false;

    // Default paths
    public const ROOT_PATH = '/';

    public const PATH_SEPARATOR = '/';

    // File categories by MIME
    public const CATEGORY_IMAGE = 'image';

    public const CATEGORY_VIDEO = 'video';

    public const CATEGORY_DOCUMENT = 'document';

    public const CATEGORY_ARCHIVE = 'archive';

    public const CATEGORY_OTHER = 'other';

    // Max file size (100MB)
    public const MAX_FILE_SIZE = 107374182400;

    // Allowed extensions
    public const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    public const ALLOWED_VIDEO_EXTENSIONS = ['mp4', 'avi', 'mov', 'wmv', 'webm'];

    public const ALLOWED_AUDIO_EXTENSIONS = ['mp3', 'm4a', 'wav', 'ogg', 'flac', 'aac'];

    public const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];

    public const ALLOWED_ARCHIVE_EXTENSIONS = ['zip', 'rar', '7z', 'tar', 'gz'];

    // Storage path patterns
    public const STORAGE_PATH_STANDARD = '{workspace}/{year}/{month}/{uuid}.{extension}';

    public const STORAGE_PATH_TEMP = '{uuid}.{extension}';

    // Disks

    public const DISK_OFFICIAL = 'minio_official';

    public const DISK_TEMP = 'minio_temp';

    public const PRESIGNED_UPLOAD_TTL = 300; // 5 minutes

    public const PRESIGNED_UPLOAD_DEFAULT_EXPIRY = 300; // 5 minutes

    // Multipart Upload Constraints
    public const MULTIPART_MIN_SIZE_16MB = 16 * 1024 * 1024;

    public const MULTIPART_MIN_SIZE_32MB = 32 * 1024 * 1024;

    public const MULTIPART_MIN_SIZE_64MB = 64 * 1024 * 1024;

    public const MULTIPART_MIN_SIZE_128MB = 128 * 1024 * 1024;

    public const SIZE_100MB = 100 * 1024 * 1024;

    public const SIZE_500MB = 500 * 1024 * 1024;

    public const SIZE_10GB = 10 * 1024 * 1024 * 1024;

    public const SIZE_100GB = 100 * 1024 * 1024 * 1024;

    public const MULTIPART_MAX_PARTS = 10000;

    // Multipart TTLs
    public const MULTIPART_TTL_SMALL = 300;   // <= 100MB

    public const MULTIPART_TTL_MEDIUM = 60;   // 100MB - 10GB

    public const MULTIPART_TTL_LARGE = 120;   // > 10GB
}
