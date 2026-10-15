import type { MediaItem } from '@/shared/types/models';

/** laravel-api UploadStatus::COMPLETED. */
const STATUS_COMPLETED = 2;

/** How often the Files listing asks again while a thumbnail or duration is on its way. */
export const MEDIA_INFO_POLL_MS = 2_000;

/** After this, a file still without them is one ffmpeg could not read: stop asking. */
export const MEDIA_INFO_WAIT_MS = 30_000;

/**
 * Whether a recent upload still waits for what ReadMediaInfoJob adds (MEDIA-11): an image or video
 * its thumbnail, a video or audio its duration.
 */
export function awaitingMediaInfo(items: MediaItem[], now = Date.now()): boolean {
  return items.some((item) => {
    if (!item.is_file || item.upload_status !== STATUS_COMPLETED || !item.updated_at) return false;
    if (now - Date.parse(item.updated_at) > MEDIA_INFO_WAIT_MS) return false;
    const needsThumbnail = item.preview === 'image' || item.preview === 'video';
    const needsDuration = item.preview === 'video' || item.preview === 'audio';
    return (needsThumbnail && !item.thumbnail_url) || (needsDuration && item.duration === null);
  });
}
