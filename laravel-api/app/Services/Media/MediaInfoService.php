<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Constants\MediaConst;
use App\Enums\PreviewKind;
use App\Models\Media\MediaMgmt;
use App\Repositories\Media\MediaRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use JsonException;

/**
 * Duration of videos and audio, thumbnails of images and videos (MEDIA-11). ffprobe / ffmpeg read the
 * object from MinIO over a presigned URL (only the ranges they need), limited in time and to the
 * demuxers of MediaConst::FFMPEG_FORMATS. A file ffmpeg cannot read simply gets neither.
 */
final class MediaInfoService
{
    private const KINDS = [PreviewKind::IMAGE, PreviewKind::VIDEO, PreviewKind::AUDIO];

    public function __construct(
        private readonly ObjectStorage $storage,
        private readonly MediaRepository $media,
    ) {}

    public static function wanted(MediaMgmt $file): bool
    {
        return $file->is_file && in_array(PreviewKind::forFile($file->extension, $file->size), self::KINDS, true);
    }

    public static function thumbnailKey(MediaMgmt $file): ?string
    {
        $key = $file->metadata[MediaConst::META_THUMBNAIL] ?? null;

        return is_string($key) ? $key : null;
    }

    public function read(MediaMgmt $file): void
    {
        $kind = PreviewKind::forFile($file->extension, $file->size);
        if (! self::wanted($file) || $file->minio_object_key === null) {
            return;
        }
        $url = $this->storage->internalGetUrl($file->minio_object_key, MediaConst::FFMPEG_TIMEOUT_SECONDS * 2);

        $probe = $kind === PreviewKind::IMAGE ? null : $this->probe($url);
        $duration = $probe['duration'] ?? null;
        $thumbnail = match (true) {
            $kind === PreviewKind::VIDEO => $this->frame($url, ($duration ?? 0) > MediaConst::THUMBNAIL_VIDEO_SECOND ? MediaConst::THUMBNAIL_VIDEO_SECOND : 0),
            $kind === PreviewKind::IMAGE && ($file->width ?? 0) * ($file->height ?? 0) <= MediaConst::THUMBNAIL_MAX_PIXELS => $this->frame($url, 0),
            default => null,
        };

        $metadata = [MediaConst::META_INFO_READ => true];
        if ($thumbnail !== null) {
            $key = MediaConst::THUMBNAIL_PREFIX.$file->minio_object_key.'.jpg';
            $this->storage->putObject($key, $thumbnail, MediaConst::THUMBNAIL_CONTENT_TYPE);
            $metadata[MediaConst::META_THUMBNAIL] = $key;
        }
        $this->media->setInfo($file, $duration, $probe['width'] ?? null, $probe['height'] ?? null, $metadata);
    }

    /**
     * Reads duration and thumbnail of files uploaded before MEDIA-11 (and of any file whose job did not run).
     *
     * @return int files read
     */
    public function readMissing(): int
    {
        $extensions = array_keys(array_filter(
            MediaConst::PREVIEW_CONTENT_TYPES,
            fn (string $type): bool => in_array(strtok($type, '/'), ['image', 'video', 'audio'], true),
        ));
        $count = 0;
        foreach ($this->media->filesWithoutInfo($extensions) as $file) {
            $this->read($file);
            $count++;
        }

        return $count;
    }

    /**
     * @return array{duration: int|null, width: int|null, height: int|null}|null
     */
    private function probe(string $url): ?array
    {
        $result = Process::timeout(MediaConst::FFMPEG_TIMEOUT_SECONDS)->run([
            'ffprobe', '-v', 'error', '-format_whitelist', MediaConst::FFMPEG_FORMATS, '-protocol_whitelist', 'http,tcp',
            '-show_entries', 'format=duration:stream=width,height', '-of', 'json', $url,
        ]);
        if (! $result->successful()) {
            Log::info('ffprobe could not read a file', ['error' => $result->errorOutput()]);

            return null;
        }
        try {
            /** @var array{format?: array{duration?: string}, streams?: list<array{width?: int, height?: int}>} $info */
            $info = json_decode($result->output(), true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
        $video = collect($info['streams'] ?? [])->first(fn (array $stream): bool => isset($stream['width'], $stream['height']));
        $duration = $info['format']['duration'] ?? null;

        return [
            'duration' => is_numeric($duration) ? (int) round((float) $duration) : null,
            'width' => $video['width'] ?? null,
            'height' => $video['height'] ?? null,
        ];
    }

    /** One frame as a JPEG at most THUMBNAIL_WIDTH wide, or null. */
    private function frame(string $url, int $second): ?string
    {
        $result = Process::timeout(MediaConst::FFMPEG_TIMEOUT_SECONDS)->run([
            'ffmpeg', '-v', 'error', '-format_whitelist', MediaConst::FFMPEG_FORMATS, '-protocol_whitelist', 'http,tcp',
            '-ss', (string) $second, '-i', $url,
            '-frames:v', '1', '-vf', "scale=w='min(".MediaConst::THUMBNAIL_WIDTH.",iw)':h=-2",
            '-f', 'image2pipe', '-c:v', 'mjpeg', '-q:v', '5', 'pipe:1',
        ]);
        if (! $result->successful() || $result->output() === '') {
            Log::info('ffmpeg could not make a thumbnail', ['error' => $result->errorOutput()]);

            return null;
        }

        return $result->output();
    }
}
