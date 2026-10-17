import type { PublicFile } from '@/lib/ledger-api';

const UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];
const KIBI = 1024;

function formatBytes(bytes: number | null): string {
  if (bytes === null) return '';
  let value = bytes;
  let unit = 0;
  while (value >= KIBI && unit < UNITS.length - 1) {
    value /= KIBI;
    unit++;
  }
  return `${unit === 0 ? value : value.toFixed(value < 10 ? 1 : 0)} ${UNITS[unit]}`;
}

// Files attached to one evidence, with their thumbnail when they have one. Images, PDF, video,
// audio and text open in the browser, the rest download (the API decides).
export function EvidenceFiles({ files }: { files: PublicFile[] }) {
  if (files.length === 0) return null;

  return (
    <ul className="mt-2 space-y-1 text-sm">
      {files.map((file) => (
        <li key={file.key} className="flex items-center gap-2">
          {file.thumbnail_url ? (
            // eslint-disable-next-line @next/next/no-img-element -- a short-lived presigned URL, not a static asset
            <img
              src={file.thumbnail_url}
              alt=""
              loading="lazy"
              className="h-10 w-10 shrink-0 rounded border object-cover"
            />
          ) : (
            <span aria-hidden>📎</span>
          )}
          <a
            href={`/skills/files/${file.key}`}
            target="_blank"
            rel="noopener noreferrer"
            className="hover:underline"
          >
            {file.name}
          </a>
          <span className="font-mono text-xs text-[hsl(var(--color-muted-foreground))]">
            {formatBytes(file.size)}
          </span>
        </li>
      ))}
    </ul>
  );
}
