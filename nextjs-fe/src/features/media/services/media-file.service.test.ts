import { beforeEach, describe, expect, it, vi } from 'vitest';
import { http, HttpResponse } from 'msw';
import { mediaFileService } from './media-file.service';
import { apiUrl, envelope, server } from '@/test/server';

const BASE = '/admin/media-mgmt';
const STORAGE_URL = 'http://storage.test/temp/abc.png';

const file = new File(['hello'], 'photo.png', { type: 'image/png' });

describe('mediaFileService', () => {
  beforeEach(() => {
    vi.spyOn(console, 'error').mockImplementation(() => {});
  });

  describe('uploadToMinio', () => {
    it('prepares a presigned URL, PUTs the file and returns its metadata', async () => {
      let prepareBody: unknown;
      let storageHeader: string | null = null;
      server.use(
        http.post(apiUrl(`${BASE}/prepare-upload`), async ({ request }) => {
          prepareBody = await request.json();
          return HttpResponse.json(
            envelope({
              upload_url: STORAGE_URL,
              key: 'temp/abc.png',
              headers: { 'x-amz-meta-test': '1' },
            }),
          );
        }),
        http.put(STORAGE_URL, ({ request }) => {
          storageHeader = request.headers.get('x-amz-meta-test');
          return new HttpResponse(null, { status: 200 });
        }),
      );

      const result = await mediaFileService.uploadToMinio({ file });

      expect(prepareBody).toEqual({
        extension: 'png',
        mime_type: 'image/png',
        original_name: 'photo.png',
        size: 5,
      });
      expect(storageHeader).toBe('1');
      expect(result).toEqual({
        key: 'temp/abc.png',
        original_name: 'photo.png',
        extension: 'png',
        mime_type: 'image/png',
        size: 5,
      });
    });

    it('rejects with the storage status when the PUT fails', async () => {
      server.use(
        http.post(apiUrl(`${BASE}/prepare-upload`), () =>
          HttpResponse.json(envelope({ upload_url: STORAGE_URL, key: 'k', headers: {} })),
        ),
        http.put(STORAGE_URL, () => new HttpResponse(null, { status: 403 })),
      );

      await expect(mediaFileService.uploadToMinio({ file })).rejects.toThrow(
        'Storage upload failed with status: 403',
      );
    });

    it('rejects when no presigned URL comes back', async () => {
      server.use(
        http.post(apiUrl(`${BASE}/prepare-upload`), () => HttpResponse.json(envelope(null))),
      );

      await expect(mediaFileService.uploadToMinio({ file })).rejects.toThrow(
        'Failed to generate upload URL',
      );
    });
  });

  describe('upload', () => {
    const captureStore = () => {
      const seen: { form?: FormData } = {};
      server.use(
        http.post(apiUrl(`${BASE}/store`), async ({ request }) => {
          seen.form = await request.formData();
          return HttpResponse.json(envelope({ media_id: 1, room_id: null, status: 2 }));
        }),
      );
      return seen;
    };

    it('commits an uploaded temp key with its metadata', async () => {
      const seen = captureStore();

      const result = await mediaFileService.upload({
        file,
        key: 'temp/abc.png',
        original_name: 'photo.png',
        extension: 'png',
        mime_type: 'image/png',
        size: 5,
        parent_path: '/docs',
        workspace_id: 0,
      });

      expect(result).toEqual({ media_id: 1, room_id: null, status: 2 });
      expect(Object.fromEntries(seen.form!.entries())).toEqual({
        key: 'temp/abc.png',
        original_name: 'photo.png',
        extension: 'png',
        mime_type: 'image/png',
        size: '5',
        parent_path: '/docs',
        workspace_id: '0',
      });
    });

    it('sends the file itself when there is no temp key', async () => {
      let body = '';
      server.use(
        http.post(apiUrl(`${BASE}/store`), async ({ request }) => {
          body = await request.text();
          return HttpResponse.json(envelope({ media_id: 1, room_id: null, status: 2 }));
        }),
      );

      await mediaFileService.upload({ file });

      expect(body).toContain('name="file"');
      expect(body).not.toContain('name="key"');
    });
  });

  it('lists files and folders', async () => {
    const queries: string[] = [];
    server.use(
      http.get(apiUrl(`${BASE}/list`), ({ request }) => {
        queries.push(new URL(request.url).search);
        return HttpResponse.json(envelope([{ id: 1 }]));
      }),
    );

    expect(await mediaFileService.list({ parent_path: '/' })).toEqual([{ id: 1 }]);
    await mediaFileService.listFolders({ parent_path: '/a' });

    expect(queries).toEqual(['?parent_path=%2F', '?parent_path=%2Fa&is_file=0']);
  });

  it('renames, moves, deletes and creates folders through the media endpoints', async () => {
    const calls: { method: string; path: string; body: unknown }[] = [];
    const record = async ({ request }: { request: Request }) => {
      const text = await request.text();
      calls.push({
        method: request.method,
        path: new URL(request.url).pathname.replace(/^.*\/admin/, '/admin'),
        body: text ? JSON.parse(text) : null,
      });
      return HttpResponse.json(envelope(1));
    };
    server.use(
      http.put(apiUrl(`${BASE}/update/:id`), record),
      http.delete(apiUrl(`${BASE}/delete/:id`), record),
      http.post(apiUrl(`${BASE}/store`), record),
    );

    await mediaFileService.rename(3, { name: 'new.png' });
    await mediaFileService.move(3, { new_parent_path: '/b' });
    await mediaFileService.delete({ ids: [4, 5] });
    await mediaFileService.createFolder({ name: 'Docs', parent_path: '/' });

    expect(calls).toEqual([
      { method: 'PUT', path: `${BASE}/update/3`, body: { name: 'new.png' } },
      { method: 'PUT', path: `${BASE}/update/3`, body: { new_parent_path: '/b' } },
      { method: 'DELETE', path: `${BASE}/delete/4`, body: { ids: [4, 5] } },
      { method: 'POST', path: `${BASE}/store`, body: { name: 'Docs', parent_path: '/' } },
    ]);
  });

  it('wraps the multipart endpoints', async () => {
    const paths: string[] = [];
    const ok =
      (data: unknown) =>
      ({ request }: { request: Request }) => {
        paths.push(new URL(request.url).pathname.split('/').pop()!);
        return HttpResponse.json(envelope(data));
      };
    server.use(
      http.post(apiUrl(`${BASE}/init-multipart-upload`), ok({ upload_id: 'u', key: 'k' })),
      http.post(apiUrl(`${BASE}/get-multipart-url`), ok({ url: 'http://part' })),
      http.post(apiUrl(`${BASE}/complete-multipart-upload`), ok({ key: 'k' })),
    );

    const meta = { extension: 'bin', size: 1, mime_type: 'x', original_name: 'a.bin' };
    expect(await mediaFileService.initMultipartUpload(meta)).toEqual({ upload_id: 'u', key: 'k' });
    expect(
      await mediaFileService.getMultipartPresignedUrl({
        key: 'k',
        upload_id: 'u',
        part_number: 1,
        size: 1,
      }),
    ).toEqual({ url: 'http://part' });
    expect(
      await mediaFileService.completeMultipartUpload({
        ...meta,
        key: 'k',
        upload_id: 'u',
        parts: [],
      }),
    ).toEqual({ key: 'k' });
    expect(paths).toEqual([
      'init-multipart-upload',
      'get-multipart-url',
      'complete-multipart-upload',
    ]);
  });

  it('formats sizes with binary units', () => {
    expect(mediaFileService.formatFileSize(0)).toBe('0.00 B');
    expect(mediaFileService.formatFileSize(1536)).toBe('1.50 KB');
    expect(mediaFileService.formatFileSize(5 * 1024 ** 3)).toBe('5.00 GB');
  });

  it('rejects copy until the backend supports it', async () => {
    await expect(mediaFileService.copy({ ids: [1], target_folder_path: '/' })).rejects.toThrow(
      'Copy functionality not yet implemented in backend',
    );
  });
});
