import { describe, expect, it, vi } from 'vitest';
import { delay, http, HttpResponse } from 'msw';
import { apiUrl, envelope, server } from '@/test/server';
import { uploadInParts, uploadSmall, UploadError } from './uploader';

const STORAGE = 'http://storage.test/media-official';
const media = (path: string) => apiUrl(`/admin/media${path}`);

describe('uploadSmall', () => {
  it('PUTs the file to the presigned URL, then completes it', async () => {
    const put = vi.fn();
    server.use(
      http.post(media('/upload-url'), () =>
        HttpResponse.json(
          envelope({
            id: 9,
            url: `${STORAGE}/a.txt?sig`,
            method: 'PUT',
            headers: { 'Content-Type': 'text/plain' },
            expires_at: '',
          }),
        ),
      ),
      http.put(`${STORAGE}/a.txt`, async ({ request }) => {
        put(request.headers.get('Content-Type'), await request.text());
        return new HttpResponse(null, { headers: { ETag: '"abc"' } });
      }),
      http.post(media('/complete/9'), () =>
        HttpResponse.json(envelope({ data: { id: 9, name: 'a.txt' } })),
      ),
    );

    const result = await uploadSmall(new File(['hello'], 'a.txt', { type: 'text/plain' }));

    expect(put).toHaveBeenCalledWith('text/plain', 'hello');
    expect(result).toMatchObject({ kind: 'completed', file: { id: 9 } });
  });
});

describe('uploadInParts', () => {
  const start = () =>
    http.post(media('/multipart/start'), () =>
      HttpResponse.json(envelope({ id: 5, part_size: 4, part_count: 3 })),
    );
  const partUrls = () =>
    http.post(media('/multipart/part-urls/5'), async ({ request }) => {
      const { part_numbers } = (await request.json()) as { part_numbers: number[] };
      return HttpResponse.json(
        envelope({
          parts: part_numbers.map((n) => ({
            part_number: n,
            url: `${STORAGE}/big?partNumber=${n}`,
          })),
          expires_at: '',
        }),
      );
    });

  it('sends every part, retries a failed one and reports the ETags', async () => {
    const bodies = new Map<string, string>();
    let failedOnce = false;
    let completed: unknown;
    server.use(
      start(),
      partUrls(),
      http.put(`${STORAGE}/big`, async ({ request }) => {
        const part = new URL(request.url).searchParams.get('partNumber') ?? '';
        if (part === '2' && !failedOnce) {
          failedOnce = true;
          return new HttpResponse(null, { status: 503 });
        }
        bodies.set(part, await request.text());
        return new HttpResponse(null, { headers: { ETag: `"etag-${part}"` } });
      }),
      http.post(media('/multipart/complete/5'), async ({ request }) => {
        completed = await request.json();
        return HttpResponse.json(
          envelope({ data: { id: 'task-1', type: 'media.process', status: 'queued' } }),
        );
      }),
    );
    const progress = vi.fn();

    const result = await uploadInParts(new File(['0123456789'], 'big.bin'), {
      onProgress: progress,
    });

    expect(Object.fromEntries(bodies)).toEqual({ '1': '0123', '2': '4567', '3': '89' });
    expect(completed).toEqual({
      parts: expect.arrayContaining([
        { part_number: 1, etag: '"etag-1"' },
        { part_number: 2, etag: '"etag-2"' },
        { part_number: 3, etag: '"etag-3"' },
      ]),
    });
    expect(progress).toHaveBeenLastCalledWith(10, 10);
    expect(result).toMatchObject({ kind: 'processing', task: { id: 'task-1' } });
  });

  it('keeps the parts already sent when a part is refused, so the upload can be resumed', async () => {
    const abort = vi.fn();
    server.use(
      start(),
      partUrls(),
      http.put(`${STORAGE}/big`, () => new HttpResponse(null, { status: 400 })),
      http.post(media('/multipart/abort/5'), () => {
        abort();
        return HttpResponse.json(envelope(null));
      }),
    );

    await expect(uploadInParts(new File(['0123456789'], 'big.bin'))).rejects.toBeInstanceOf(
      UploadError,
    );
    expect(abort).not.toHaveBeenCalled();
  });

  it('starts no new part once one has failed', async () => {
    const sent: string[] = [];
    server.use(
      http.post(media('/multipart/start'), () =>
        HttpResponse.json(envelope({ id: 5, part_size: 1, part_count: 8 })),
      ),
      partUrls(),
      http.put(`${STORAGE}/big`, async ({ request }) => {
        const part = new URL(request.url).searchParams.get('partNumber') ?? '';
        sent.push(part);
        if (part === '1') return new HttpResponse(null, { status: 400 });
        await delay(50);
        return new HttpResponse(null, { headers: { ETag: `"etag-${part}"` } });
      }),
    );

    await expect(uploadInParts(new File(['01234567'], 'big.bin'))).rejects.toBeInstanceOf(
      UploadError,
    );
    await delay(200);
    // Only the 4 parts already on their way; parts 5-8 never start
    expect(sent.sort()).toEqual(['1', '2', '3', '4']);
  });

  it('drops the parts on the server when the upload is cancelled', async () => {
    const abort = vi.fn();
    const controller = new AbortController();
    server.use(
      start(),
      partUrls(),
      http.put(`${STORAGE}/big`, () => {
        controller.abort();
        return new HttpResponse(null, { status: 503 });
      }),
      http.post(media('/multipart/abort/5'), () => {
        abort();
        return HttpResponse.json(envelope(null));
      }),
    );

    await expect(
      uploadInParts(new File(['0123456789'], 'big.bin'), { signal: controller.signal }),
    ).rejects.toBeInstanceOf(UploadError);
    expect(abort).toHaveBeenCalledOnce();
  });

  it('resumes with only the missing parts, resending a part that was cut short', async () => {
    const sent: string[] = [];
    let completed: unknown;
    server.use(
      http.post(media('/multipart/resume/5'), () =>
        HttpResponse.json(
          envelope({
            id: 5,
            part_size: 4,
            part_count: 3,
            parts: [
              { part_number: 1, etag: 'etag-1', size: 4 },
              { part_number: 2, etag: 'etag-2-short', size: 2 },
            ],
          }),
        ),
      ),
      partUrls(),
      http.put(`${STORAGE}/big`, ({ request }) => {
        const part = new URL(request.url).searchParams.get('partNumber') ?? '';
        sent.push(part);
        return new HttpResponse(null, { headers: { ETag: `"etag-${part}"` } });
      }),
      http.post(media('/multipart/complete/5'), async ({ request }) => {
        completed = await request.json();
        return HttpResponse.json(
          envelope({ data: { id: 'task-1', type: 'media.process', status: 'queued' } }),
        );
      }),
    );
    const progress = vi.fn();
    const started = vi.fn();

    await uploadInParts(new File(['0123456789'], 'big.bin'), {
      resumeId: 5,
      onProgress: progress,
      onStarted: started,
    });

    expect(started).toHaveBeenCalledWith(5);
    expect(progress).toHaveBeenNthCalledWith(1, 4, 10);
    expect(sent.sort()).toEqual(['2', '3']);
    expect(completed).toEqual({
      parts: expect.arrayContaining([
        { part_number: 1, etag: 'etag-1' },
        { part_number: 2, etag: '"etag-2"' },
        { part_number: 3, etag: '"etag-3"' },
      ]),
    });
  });

  it('refuses to resume with a different file of the same name and size', async () => {
    const resume = vi.fn();
    server.use(
      start(),
      partUrls(),
      http.put(`${STORAGE}/big`, () => new HttpResponse(null, { status: 400 })),
      http.post(media('/multipart/resume/5'), () => {
        resume();
        return HttpResponse.json(envelope(null));
      }),
    );
    // Started in this browser with one file...
    await expect(
      uploadInParts(new File(['0123456789'], 'big.bin', { lastModified: 1000 })),
    ).rejects.toBeInstanceOf(UploadError);

    // ...resumed with another one
    await expect(
      uploadInParts(new File(['abcdefghij'], 'big.bin', { lastModified: 2000 }), {
        resumeId: 5,
      }),
    ).rejects.toThrow('not the file');
    expect(resume).not.toHaveBeenCalled();
  });
});
