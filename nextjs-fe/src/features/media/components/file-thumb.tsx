import { File as FileIcon } from 'lucide-react';

/**
 * The small picture of a file in lists (MEDIA-11): its thumbnail (an image, or a video frame) when the
 * server made one, else the file icon.
 */
export function FileThumb({ item }: { item: { thumbnail_url?: string | null } }) {
  if (!item.thumbnail_url) {
    return <FileIcon className="h-4 w-4 shrink-0 text-slate-400" aria-hidden />;
  }
  return (
    // eslint-disable-next-line @next/next/no-img-element -- a short-lived presigned URL, not a static asset
    <img
      src={item.thumbnail_url}
      alt=""
      loading="lazy"
      className="h-8 w-8 shrink-0 rounded border object-cover"
    />
  );
}
