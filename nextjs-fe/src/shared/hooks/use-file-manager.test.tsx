import { beforeEach, describe, expect, it, vi } from 'vitest';
import { act, renderHook, waitFor } from '@testing-library/react';
import toast from 'react-hot-toast';
import { useFileManager } from './use-file-manager';
import { mediaFileService } from '@/shared/services/modules/media-file.service';
import { notification } from '@/shared/utils';
import { UploadStatus } from '@/shared/enums/enums';
import type { FileManagerContextType } from '@/shared/types/file-manager.types';
import type { MediaApiListResponse, UploadResponse } from '@/shared/types/media-file.types';

// A stable translator: the hook re-fetches whenever `t` changes identity.
vi.mock('next-intl', () => {
  const t = (key: string) => key;
  return { useTranslations: () => t };
});
vi.mock('react-hot-toast', () => ({
  default: { success: vi.fn(), error: vi.fn() },
}));
vi.mock('@/shared/utils', () => ({
  notification: { info: vi.fn() },
}));
vi.mock('@/shared/utils/upload-debug', () => ({
  UploadDebugger: { logStoreResponse: vi.fn() },
}));
vi.mock('@/shared/services/modules/media-file.service', () => ({
  mediaFileService: {
    list: vi.fn(),
    createFolder: vi.fn(),
    upload: vi.fn(),
    uploadToMinio: vi.fn(),
    delete: vi.fn(),
    rename: vi.fn(),
    move: vi.fn(),
    copy: vi.fn(),
  },
}));

const service = vi.mocked(mediaFileService);
const listOf = (items: unknown[]) => items as unknown as MediaApiListResponse;

const apiFiles = [
  {
    id: 1,
    original_name: 'Beta.png',
    mime_type: 'image/png',
    view_url: 'http://cdn/beta.png',
    size: 300,
    is_file: true,
    created_at: '2026-01-02',
  },
  {
    id: 2,
    original_name: 'alpha.mp4',
    mime_type: 'video/mp4',
    url: 'http://cdn/alpha.mp4',
    size: 100,
    is_file: true,
    created_at: '2026-01-03',
  },
  {
    id: 3,
    original_name: 'Docs',
    mime_type: null,
    size: 0,
    is_file: false,
    created_at: '2026-01-01',
  },
  {
    id: 4,
    original_name: 'report.pdf',
    mime_type: 'application/pdf',
    size: 200,
    is_file: true,
    workspace_id: 9,
    folder_path: '/reports',
    created_at: '2026-01-04',
  },
];

const renderManager = async () => {
  // The context type marks actions optional; the hook always provides them.
  const hook = renderHook(() => useFileManager() as Required<FileManagerContextType>);
  await waitFor(() => expect(hook.result.current.isLoading).toBe(false));
  return hook;
};

const names = (hook: Awaited<ReturnType<typeof renderManager>>) =>
  hook.result.current.files.map((file) => file.name);

describe('useFileManager', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(console, 'log').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});
    service.list.mockResolvedValue(listOf(apiFiles));
  });

  describe('listing', () => {
    it('loads the current folder and maps API rows to files', async () => {
      const hook = await renderManager();

      expect(service.list).toHaveBeenCalledWith({ parent_path: '/' }, expect.anything());
      const [image] = hook.result.current.files.filter((file) => file.id === '1');
      expect(image).toMatchObject({
        id: '1',
        drive_id: '1',
        name: 'Beta.png',
        url: 'http://cdn/beta.png',
        thumbnail_url: 'http://cdn/beta.png',
        folder_path: '/',
        owner_id: '0',
        type: 'file',
      });
      const pdf = hook.result.current.files.find((file) => file.id === '4');
      expect(pdf).toMatchObject({
        owner_id: '9',
        folder_path: '/reports',
        thumbnail_url: undefined,
      });
      expect(hook.result.current.files.find((file) => file.id === '3')?.type).toBe('folder');
    });

    it('accepts a { data: [...] } list response', async () => {
      service.list.mockResolvedValue({
        data: apiFiles.slice(0, 1),
      } as unknown as MediaApiListResponse);
      const hook = await renderManager();

      expect(names(hook)).toEqual(['Beta.png']);
    });

    it('shows an error and an empty list when loading fails', async () => {
      service.list.mockRejectedValue(new Error('down'));
      const hook = await renderManager();

      expect(hook.result.current.files).toEqual([]);
      expect(toast.error).toHaveBeenCalledWith('listError');
    });

    it('reloads when the path changes and clears the selection', async () => {
      const hook = await renderManager();
      act(() => hook.result.current.toggleFileSelection('1', true));

      act(() => hook.result.current.setCurrentPath('/reports'));
      await waitFor(() =>
        expect(service.list).toHaveBeenLastCalledWith(
          { parent_path: '/reports' },
          expect.anything(),
        ),
      );
      expect(hook.result.current.selectedFiles).toEqual([]);
    });
  });

  describe('client-side search, filter and sort', () => {
    it('sorts by newest first by default', async () => {
      const hook = await renderManager();

      expect(names(hook)).toEqual(['report.pdf', 'alpha.mp4', 'Beta.png', 'Docs']);
    });

    it('sorts by name, size and type in both directions', async () => {
      const hook = await renderManager();

      act(() => hook.result.current.setSortOptions({ field: 'name', order: 'asc' }));
      expect(names(hook)).toEqual(['alpha.mp4', 'Beta.png', 'Docs', 'report.pdf']);

      act(() => hook.result.current.setSortOptions({ field: 'size', order: 'desc' }));
      expect(names(hook)).toEqual(['Beta.png', 'report.pdf', 'alpha.mp4', 'Docs']);

      act(() => hook.result.current.setSortBy('type'));
      expect(names(hook)).toEqual(['alpha.mp4', 'Beta.png', 'report.pdf', 'Docs']);
    });

    it('searches names case-insensitively', async () => {
      const hook = await renderManager();

      act(() => hook.result.current.setSearchQuery('ALPHA'));
      expect(names(hook)).toEqual(['alpha.mp4']);
    });

    it.each([
      ['folders', ['Docs']],
      ['images', ['Beta.png']],
      ['videos', ['alpha.mp4']],
      ['documents', ['report.pdf']],
      ['all', ['report.pdf', 'alpha.mp4', 'Beta.png', 'Docs']],
    ])('filters by %s', async (type, expected) => {
      const hook = await renderManager();

      act(() => hook.result.current.setFilterType(type));
      expect(hook.result.current.filterType).toBe(type);
      expect(names(hook)).toEqual(expected);
    });
  });

  describe('selection', () => {
    it('toggles single files without duplicates and selects all', async () => {
      const hook = await renderManager();

      act(() => hook.result.current.toggleFileSelection('1', true));
      act(() => hook.result.current.toggleFileSelection('1', true));
      act(() => hook.result.current.toggleFileSelection('2', true));
      expect(hook.result.current.selectedFiles).toEqual(['1', '2']);

      act(() => hook.result.current.toggleFileSelection('1', false));
      expect(hook.result.current.selectedFiles).toEqual(['2']);

      act(() => hook.result.current.selectAllFiles(true));
      expect(hook.result.current.selectedFiles).toHaveLength(4);
      act(() => hook.result.current.selectAllFiles(false));
      expect(hook.result.current.selectedFiles).toEqual([]);
    });
  });

  describe('file operations', () => {
    it('creates a folder in the current path and reloads', async () => {
      const hook = await renderManager();
      service.list.mockClear();

      await act(() => hook.result.current.createFolder('New'));

      expect(service.createFolder).toHaveBeenCalledWith({ name: 'New', parent_path: '/' });
      expect(toast.success).toHaveBeenCalledWith('createFolderSuccess');
      expect(service.list).toHaveBeenCalledTimes(1);
    });

    it('deletes, renames, moves and copies with numeric ids', async () => {
      const hook = await renderManager();
      act(() => hook.result.current.selectAllFiles(true));

      await act(() => hook.result.current.deleteFiles(['1', '2']));
      expect(service.delete).toHaveBeenCalledWith({ ids: [1, 2] });
      expect(hook.result.current.selectedFiles).toEqual(['4', '3']);

      await act(() => hook.result.current.renameFile('4', 'final.pdf'));
      expect(service.rename).toHaveBeenCalledWith(4, { name: 'final.pdf' });

      await act(() => hook.result.current.moveFiles(['4', '3'], '/archive'));
      expect(service.move.mock.calls).toEqual([
        [4, { new_parent_path: '/archive' }],
        [3, { new_parent_path: '/archive' }],
      ]);
      expect(hook.result.current.selectedFiles).toEqual([]);

      await act(() => hook.result.current.copyFiles(['4'], '/copy'));
      expect(service.copy).toHaveBeenCalledWith({ ids: [4], target_folder_path: '/copy' });

      expect(vi.mocked(toast.success).mock.calls.flat()).toEqual([
        'deleteSuccess',
        'renameSuccess',
        'moveSuccess',
        'copySuccess',
      ]);
    });

    it('shows an error toast for each failed operation', async () => {
      const hook = await renderManager();
      const boom = new Error('boom');
      service.createFolder.mockRejectedValue(boom);
      service.delete.mockRejectedValue(boom);
      service.rename.mockRejectedValue(boom);
      service.move.mockRejectedValue(boom);
      service.copy.mockRejectedValue(boom);

      await act(() => hook.result.current.createFolder('x'));
      await act(() => hook.result.current.deleteFiles(['1']));
      await act(() => hook.result.current.renameFile('1', 'x'));
      await act(() => hook.result.current.moveFiles(['1'], '/x'));
      await act(() => hook.result.current.copyFiles(['1'], '/x'));

      expect(vi.mocked(toast.error).mock.calls.flat()).toEqual([
        'createFolderError',
        'deleteError',
        'renameError',
        'moveError',
        'copyError',
      ]);
    });
  });

  describe('uploadFiles', () => {
    const tempMetadata = {
      original_name: 'big.mov',
      extension: 'mov',
      mime_type: 'video/quicktime',
      size: 99,
    };
    const preUploaded = () =>
      Object.assign(new File(['x'], 'big.mov'), { tempKey: 'temp/big.mov', tempMetadata });
    const stored = (values: Partial<UploadResponse>): UploadResponse => ({
      media_id: 10,
      room_id: 'room-10',
      status: UploadStatus.COMPLETED,
      message: 'ok',
      ...values,
    });

    it('commits a pre-uploaded file and reloads when it completes immediately', async () => {
      service.upload.mockResolvedValue(stored({}));
      const hook = await renderManager();
      service.list.mockClear();

      await act(() => hook.result.current.uploadFiles([preUploaded()]));

      expect(service.upload).toHaveBeenCalledWith(
        expect.objectContaining({ key: 'temp/big.mov', parent_path: '/', ...tempMetadata }),
      );
      expect(toast.success).toHaveBeenCalledWith('uploadSuccess 1 file(s)');
      expect(service.list).toHaveBeenCalledTimes(1);
      expect(hook.result.current.heavyUploads).toEqual([]);
    });

    it('registers a heavy upload that is still processing', async () => {
      service.upload.mockResolvedValue(stored({ status: UploadStatus.PROCESSING }));
      const hook = await renderManager();
      service.list.mockResolvedValue(listOf([{ upload_status: UploadStatus.PROCESSING }]));

      await act(() => hook.result.current.uploadFiles([preUploaded()]));

      expect(notification.info).toHaveBeenCalledWith('ok', { duration: 4000 });
      expect(service.list).toHaveBeenLastCalledWith({ id: 10 });
      expect(hook.result.current.heavyUploads).toEqual([
        { roomId: 'room-10', fileName: 'big.mov', mediaId: 10 },
      ]);

      act(() => hook.result.current.clearHeavyUpload('room-10'));
      expect(hook.result.current.heavyUploads).toEqual([]);
    });

    it('treats a heavy upload that already finished as immediate or failed', async () => {
      service.upload.mockResolvedValue(stored({ status: UploadStatus.PROCESSING }));
      const hook = await renderManager();

      service.list.mockResolvedValue({
        data: [{ upload_status: UploadStatus.COMPLETED }],
      } as unknown as MediaApiListResponse);
      await act(() => hook.result.current.uploadFiles([preUploaded()]));
      expect(hook.result.current.heavyUploads).toEqual([]);
      expect(toast.success).toHaveBeenCalledWith('uploadSuccess');

      service.list.mockResolvedValue(listOf([{ upload_status: UploadStatus.FAILED }]));
      await act(() => hook.result.current.uploadFiles([preUploaded()]));
      expect(toast.error).toHaveBeenCalledWith('uploadError');
      expect(toast.error).toHaveBeenCalledWith('1 file(s) failed to upload');
    });

    it('waits for the WebSocket when the status check fails', async () => {
      service.upload.mockResolvedValue(stored({ status: UploadStatus.PROCESSING }));
      const hook = await renderManager();
      service.list.mockRejectedValue(new Error('offline'));

      await act(() => hook.result.current.uploadFiles([preUploaded()]));

      expect(hook.result.current.heavyUploads).toHaveLength(1);
    });

    it('uploads plain files to temp storage first', async () => {
      const metadata = { key: 'temp/a.txt', ...tempMetadata, original_name: 'a.txt' };
      service.uploadToMinio.mockResolvedValue(metadata);
      service.upload.mockResolvedValue(stored({}));
      const hook = await renderManager();

      await act(() => hook.result.current.uploadFiles([new File(['a'], 'a.txt')]));

      expect(service.upload).toHaveBeenCalledWith(
        expect.objectContaining({ parent_path: '/', ...metadata }),
      );
      expect(toast.success).toHaveBeenCalledWith('uploadSuccess 1 file(s)');
    });

    it('handles heavy plain files the same way', async () => {
      service.uploadToMinio.mockResolvedValue({ key: 'temp/a.mov', ...tempMetadata });
      service.upload.mockResolvedValue(stored({ status: UploadStatus.PROCESSING, message: '' }));
      const hook = await renderManager();

      service.list.mockResolvedValue(listOf([{ upload_status: UploadStatus.PROCESSING }]));
      await act(() => hook.result.current.uploadFiles([new File(['a'], 'a.mov')]));
      expect(hook.result.current.heavyUploads).toHaveLength(1);

      service.list.mockResolvedValue(listOf([{ upload_status: UploadStatus.COMPLETED }]));
      await act(() => hook.result.current.uploadFiles([new File(['a'], 'a.mov')]));
      expect(hook.result.current.heavyUploads).toHaveLength(1);

      service.list.mockResolvedValue(listOf([{ upload_status: UploadStatus.FAILED }]));
      await act(() => hook.result.current.uploadFiles([new File(['a'], 'a.mov')]));

      service.list.mockRejectedValue(new Error('offline'));
      await act(() => hook.result.current.uploadFiles([new File(['a'], 'a.mov')]));
      expect(hook.result.current.heavyUploads).toHaveLength(2);
      expect(toast.error).toHaveBeenCalledWith('uploadError');
    });

    it('reports per-file failures without throwing', async () => {
      service.uploadToMinio.mockRejectedValueOnce(new Error('denied')).mockResolvedValueOnce(null);
      const hook = await renderManager();

      await act(() =>
        hook.result.current.uploadFiles([new File(['a'], 'a.txt'), new File(['b'], 'b.txt')]),
      );

      expect(toast.error).toHaveBeenCalledWith('a.txt: denied');
      expect(toast.error).toHaveBeenCalledWith('2 file(s) failed to upload');
      expect(hook.result.current.isLoading).toBe(false);
    });
  });
});
