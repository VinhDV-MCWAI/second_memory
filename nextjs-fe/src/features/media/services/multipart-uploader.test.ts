import { beforeEach, describe, expect, it, vi } from 'vitest';
import { http, HttpResponse } from 'msw';
import { MultipartUploader } from './multipart-uploader';
import { mediaFileService } from './media-file.service';
import { server } from '@/test/server';

vi.mock('./media-file.service', () => ({
  mediaFileService: {
    initMultipartUpload: vi.fn(),
    getMultipartPresignedUrl: vi.fn(),
    completeMultipartUpload: vi.fn(),
  },
}));

// Retry instantly and give up after two attempts so failure paths stay fast.
vi.mock('@/shared/config', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/shared/config')>();
  return {
    ...actual,
    MULTIPART_UPLOAD_CONFIG: {
      ...actual.MULTIPART_UPLOAD_CONFIG,
      MAX_RETRIES: 2,
      RETRY_DELAY_BASE: 0,
      RETRY_JITTER: 0,
    },
  };
});

const PART_URL = 'http://storage.test/part';
const service = vi.mocked(mediaFileService);

// 10 bytes in parts of 4 → 3 parts.
const file = new File(['0123456789'], 'video.mp4', { type: 'video/mp4' });

describe('MultipartUploader', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(console, 'info').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});
    service.initMultipartUpload.mockResolvedValue({
      upload_id: 'up-1',
      key: 'temp/video.mp4',
      part_size: 4,
      parts_count: 3,
    });
    service.getMultipartPresignedUrl.mockImplementation(async ({ part_number }) => ({
      url: `${PART_URL}/${part_number}`,
      part_number,
      expires_in: 60,
    }));
    service.completeMultipartUpload.mockResolvedValue({
      key: 'official/video.mp4',
      original_name: 'video.mp4',
      extension: 'mp4',
      mime_type: 'video/mp4',
      size: 10,
    });
  });

  it('uploads every part and completes with sorted ETags', async () => {
    const sizes: Record<string, number> = {};
    server.use(
      http.put(`${PART_URL}/:part`, async ({ request, params }) => {
        sizes[params.part as string] = (await request.arrayBuffer()).byteLength;
        return new HttpResponse(null, { headers: { ETag: `"etag-${params.part}"` } });
      }),
    );

    const result = await new MultipartUploader(file, () => {}, 7, '/videos').start();

    expect(service.initMultipartUpload).toHaveBeenCalledWith({
      extension: 'mp4',
      size: 10,
      mime_type: 'video/mp4',
      original_name: 'video.mp4',
    });
    expect(sizes).toEqual({ 1: 4, 2: 4, 3: 2 });
    expect(service.completeMultipartUpload).toHaveBeenCalledWith({
      key: 'temp/video.mp4',
      upload_id: 'up-1',
      parts: [
        { part_number: 1, etag: 'etag-1' },
        { part_number: 2, etag: 'etag-2' },
        { part_number: 3, etag: 'etag-3' },
      ],
      original_name: 'video.mp4',
      extension: 'mp4',
      size: 10,
      mime_type: 'video/mp4',
      workspace_id: 7,
      parent_path: '/videos',
    });
    expect(result).toEqual({
      original_name: 'video.mp4',
      extension: 'mp4',
      size: 10,
      mime_type: 'video/mp4',
      key: 'official/video.mp4',
    });
  });

  it('retries a failed part and reuses its presigned URL', async () => {
    let failures = 1;
    server.use(
      http.put(`${PART_URL}/:part`, ({ params }) => {
        if (params.part === '2' && failures-- > 0) {
          return new HttpResponse(null, { status: 500 });
        }
        return new HttpResponse(null, { headers: { ETag: `"e${params.part}"` } });
      }),
    );

    await new MultipartUploader(file, () => {}).start();

    expect(service.getMultipartPresignedUrl).toHaveBeenCalledTimes(3);
    expect(service.completeMultipartUpload).toHaveBeenCalledTimes(1);
  });

  it('gives up after the maximum attempts', async () => {
    server.use(http.put(`${PART_URL}/:part`, () => new HttpResponse(null, { status: 500 })));

    await expect(new MultipartUploader(file, () => {}).start()).rejects.toThrow(
      'failed after 2 attempts',
    );
    expect(service.completeMultipartUpload).not.toHaveBeenCalled();
  });

  it('treats a response without ETag as a failure', async () => {
    server.use(http.put(`${PART_URL}/:part`, () => new HttpResponse(null, { status: 200 })));

    await expect(new MultipartUploader(file, () => {}).start()).rejects.toThrow(
      'failed after 2 attempts',
    );
  });
});
