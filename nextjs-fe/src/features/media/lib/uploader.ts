import { apiClient, API_ENDPOINTS } from '@/shared/api';
import type {
  BackgroundTask,
  MediaItem,
  MultipartUpload,
  PartUrls,
  ResumedUpload,
  SmallUpload,
} from '@/shared/types/models';

/**
 * Browser side of the file upload (ADR-0014). Bytes go straight to MinIO through presigned URLs:
 * a small file in one PUT, a large one in parts, 4 at a time, each retried with a growing pause.
 * The server decides the part size; part URLs are fetched in batches as the workers get to them.
 * A large upload cut off midway (reload, network) keeps its parts on the server and can be resumed
 * with the same file: only the missing parts are sent. Only the cancel button drops them.
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
  /** localStorage key: upload id → lastModified of the file it was started with. */
  FILES_KEY: 'media.multipart.files',
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
  /** Continue this unfinished multipart upload (`media/multipart/unfinished`) instead of starting one. */
  resumeId?: number;
  /** Id of the multipart upload, once started or resumed. */
  onStarted?: (id: number) => void;
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
  return file.size <= UPLOAD.SINGLE_MAX_BYTES && options.resumeId === undefined
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
  { folder, onProgress, signal, resumeId, onStarted }: UploadOptions = {},
): Promise<UploadResult> {
  const base = API_ENDPOINTS.MEDIA.MULTIPART;
  const loaded = new Map<number, number>();
  const etags = new Map<number, string>();
  let upload: MultipartUpload;

  if (resumeId === undefined) {
    ({ data: upload } = await apiClient.post<MultipartUpload>(`${base}/start`, {
      folder,
      name: file.name,
      size: file.size,
      mime_type: mimeTypeOf(file),
    }));
    rememberFile(upload.id, file);
  } else {
    if (!isSameFile(resumeId, file)) {
      throw new UploadError('This is not the file the upload was started with');
    }
    const { data: resumed } = await apiClient.post<ResumedUpload>(`${base}/resume/${resumeId}`);
    upload = resumed;
    // A part shorter than it should be (cut off mid-PUT) is sent again
    for (const part of resumed.parts ?? []) {
      const size = partBytes(part.part_number, resumed.part_size, file.size);
      if (part.size === size) {
        etags.set(part.part_number, part.etag);
        loaded.set(part.part_number, size);
      }
    }
  }
  onStarted?.(upload.id);

  const urls = new Map<number, string>();
  // One part failed for good: the other workers stop too, so nothing more goes up in the background
  let stopped = false;
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
    const body = file.slice(start, start + partBytes(partNumber, upload.part_size, file.size));
    let refresh = false;
    for (let attempt = 1; ; attempt++) {
      // Also checked after the pause before a retry
      if (stopped) throw new UploadError('Upload stopped');
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

  report();
  try {
    const queue = range(1, upload.part_count).filter((n) => !etags.has(n));
    const worker = async () => {
      try {
        for (let next = queue.shift(); next !== undefined && !stopped; next = queue.shift()) {
          await sendPart(next);
        }
      } catch (error) {
        stopped = true;
        throw error;
      }
    };
    await Promise.all(
      Array.from({ length: Math.min(UPLOAD.CONCURRENCY, upload.part_count) }, worker),
    );
  } catch (error) {
    // Cancelled: free the space now. Otherwise keep the parts so the upload can be resumed
    if (signal?.aborted) {
      await apiClient.post(`${base}/abort/${upload.id}`).catch(() => undefined);
      forgetFile(upload.id);
    }
    throw error;
  }

  const parts = [...etags.entries()].map(([part_number, etag]) => ({ part_number, etag }));
  const { data } = await apiClient.post<{ data: BackgroundTask }>(`${base}/complete/${upload.id}`, {
    parts,
  });
  forgetFile(upload.id);
  return { kind: 'processing', task: data.data };
}

/** Cancel an unfinished upload from an earlier visit: the server drops its parts. */
export async function discardUpload(id: number): Promise<void> {
  await apiClient.post(`${API_ENDPOINTS.MEDIA.MULTIPART}/abort/${id}`);
  forgetFile(id);
}

/** Bytes of one part: part_size, except the last one. */
function partBytes(partNumber: number, partSize: number, fileSize: number): number {
  return Math.max(0, Math.min(partSize, fileSize - (partNumber - 1) * partSize));
}

/**
 * Name and size are matched by the caller; the modification time (kept in this browser only) catches a
 * different file with the same name and size, whose parts would mix into a broken file.
 */
function isSameFile(id: number, file: File): boolean {
  const lastModified = readFiles()[id];
  return lastModified === undefined || lastModified === file.lastModified;
}

function rememberFile(id: number, file: File): void {
  writeFiles({ ...readFiles(), [id]: file.lastModified });
}

function forgetFile(id: number): void {
  const files = readFiles();
  delete files[id];
  writeFiles(files);
}

function readFiles(): Record<number, number> {
  try {
    return JSON.parse(localStorage.getItem(UPLOAD.FILES_KEY) ?? '{}') as Record<number, number>;
  } catch {
    return {};
  }
}

function writeFiles(files: Record<number, number>): void {
  try {
    localStorage.setItem(UPLOAD.FILES_KEY, JSON.stringify(files));
  } catch {
    // Storage blocked or full: resuming still works, without the modification-time check
  }
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
