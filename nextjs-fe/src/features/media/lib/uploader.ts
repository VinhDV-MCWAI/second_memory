import { apiClient, API_ENDPOINTS } from '@/shared/api';
import type {
  BackgroundTask,
  MediaItem,
  MultipartUpload,
  PartUrls,
  SmallUpload,
} from '@/shared/types/models';

/**
 * Browser side of the file upload (ADR-0014). Bytes go straight to MinIO through presigned URLs:
 * a small file in one PUT, a large one in parts, 4 at a time, each retried with a growing pause.
 * The server decides the part size; part URLs are fetched in batches as the workers get to them.
 */
export const UPLOAD = {
  /** Mirrors MediaConst::SINGLE_UPLOAD_MAX_BYTES. */
  SINGLE_MAX_BYTES: 100 * 1024 * 1024,
  CONCURRENCY: 4,
  /** Tries per part; between them 1 s, 2 s, … plus up to 100 ms jitter. */
  MAX_TRIES: 3,
  RETRY_BASE_MS: 1000,
  /** Mirrors MediaConst::PART_URLS_PER_REQUEST. */
  URL_BATCH: 100,
} as const;

const HTTP_FORBIDDEN = 403;
const HTTP_SERVER_ERROR = 500;

export type UploadResult =
  | { kind: 'completed'; file: MediaItem }
  /** A large file: joined and checked by a background job, the result arrives as a live event. */
  | { kind: 'processing'; task: BackgroundTask };

export interface UploadOptions {
  folder?: string;
  /** Bytes sent so far out of the file size. */
  onProgress?: (loaded: number, total: number) => void;
  signal?: AbortSignal;
}

export class UploadError extends Error {
  constructor(
    message: string,
    readonly status?: number,
  ) {
    super(message);
    this.name = 'UploadError';
  }
}

export async function uploadFile(file: File, options: UploadOptions = {}): Promise<UploadResult> {
  return file.size <= UPLOAD.SINGLE_MAX_BYTES
    ? uploadSmall(file, options)
    : uploadInParts(file, options);
}

export async function uploadSmall(
  file: File,
  { folder, onProgress, signal }: UploadOptions = {},
): Promise<UploadResult> {
  const { data: upload } = await apiClient.post<SmallUpload>(
    `${API_ENDPOINTS.MEDIA.BASE}/upload-url`,
    {
      folder,
      name: file.name,
      size: file.size,
      mime_type: mimeTypeOf(file),
    },
  );
  await put(upload.url, file, upload.headers, (loaded) => onProgress?.(loaded, file.size), signal);
  const { data } = await apiClient.post<{ data: MediaItem }>(
    `${API_ENDPOINTS.MEDIA.BASE}/complete/${upload.id}`,
  );
  return { kind: 'completed', file: data.data };
}

export async function uploadInParts(
  file: File,
  { folder, onProgress, signal }: UploadOptions = {},
): Promise<UploadResult> {
  const base = API_ENDPOINTS.MEDIA.MULTIPART;
  const { data: upload } = await apiClient.post<MultipartUpload>(`${base}/start`, {
    folder,
    name: file.name,
    size: file.size,
    mime_type: mimeTypeOf(file),
  });

  const urls = new Map<number, string>();
  const loaded = new Map<number, number>();
  const etags = new Map<number, string>();
  const report = () =>
    onProgress?.(
      [...loaded.values()].reduce((sum, bytes) => sum + bytes, 0),
      file.size,
    );

  const urlFor = async (partNumber: number, refresh = false): Promise<string> => {
    if (refresh || !urls.has(partNumber)) {
      const numbers = refresh
        ? [partNumber]
        : range(partNumber, Math.min(upload.part_count, partNumber + UPLOAD.URL_BATCH - 1)).filter(
            (n) => !urls.has(n),
          );
      const { data } = await apiClient.post<PartUrls>(`${base}/part-urls/${upload.id}`, {
        part_numbers: numbers,
      });
      for (const part of data.parts) urls.set(part.part_number, part.url);
    }
    return urls.get(partNumber) ?? '';
  };

  const sendPart = async (partNumber: number): Promise<void> => {
    const start = (partNumber - 1) * upload.part_size;
    const body = file.slice(start, Math.min(start + upload.part_size, file.size));
    let refresh = false;
    for (let attempt = 1; ; attempt++) {
      try {
        const etag = await put(
          await urlFor(partNumber, refresh),
          body,
          {},
          (bytes) => {
            loaded.set(partNumber, bytes);
            report();
          },
          signal,
        );
        etags.set(partNumber, etag);
        return;
      } catch (error) {
        loaded.set(partNumber, 0);
        report();
        const status = error instanceof UploadError ? error.status : undefined;
        if (
          signal?.aborted ||
          attempt >= UPLOAD.MAX_TRIES ||
          (status !== undefined && status < HTTP_SERVER_ERROR && status !== HTTP_FORBIDDEN)
        ) {
          throw error;
        }
        // 403: the URL expired while waiting (slow link), ask for a fresh one
        refresh = status === HTTP_FORBIDDEN;
        await sleep(UPLOAD.RETRY_BASE_MS * 2 ** (attempt - 1) + Math.random() * 100);
      }
    }
  };

  try {
    const queue = range(1, upload.part_count);
    const worker = async () => {
      for (let next = queue.shift(); next !== undefined; next = queue.shift()) await sendPart(next);
    };
    await Promise.all(
      Array.from({ length: Math.min(UPLOAD.CONCURRENCY, upload.part_count) }, worker),
    );
  } catch (error) {
    // Free the name and the space now instead of waiting for the hourly clean-up
    await apiClient.post(`${base}/abort/${upload.id}`).catch(() => undefined);
    throw error;
  }

  const parts = [...etags.entries()].map(([part_number, etag]) => ({ part_number, etag }));
  const { data } = await apiClient.post<{ data: BackgroundTask }>(`${base}/complete/${upload.id}`, {
    parts,
  });
  return { kind: 'processing', task: data.data };
}

/** PUT with upload progress (fetch has none); resolves with the ETag MinIO returns. */
function put(
  url: string,
  body: Blob,
  headers: Record<string, string>,
  onProgress: (loaded: number) => void,
  signal?: AbortSignal,
): Promise<string> {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open('PUT', url);
    for (const [name, value] of Object.entries(headers)) xhr.setRequestHeader(name, value);
    xhr.upload.onprogress = (event) => onProgress(event.loaded);
    xhr.onload = () => {
      if (xhr.status >= 200 && xhr.status < 300) {
        onProgress(body.size);
        resolve(xhr.getResponseHeader('ETag') ?? '');
      } else {
        reject(new UploadError(`Upload failed with HTTP ${xhr.status}`, xhr.status));
      }
    };
    xhr.onerror = () => reject(new UploadError('Network error'));
    xhr.onabort = () => reject(new UploadError('Upload cancelled'));
    if (signal?.aborted) {
      reject(new UploadError('Upload cancelled'));
      return;
    }
    signal?.addEventListener('abort', () => xhr.abort(), { once: true });
    xhr.send(body);
  });
}

function mimeTypeOf(file: File): string {
  return file.type || 'application/octet-stream';
}

function range(from: number, to: number): number[] {
  return Array.from({ length: Math.max(0, to - from + 1) }, (_, i) => from + i);
}

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}
