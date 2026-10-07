import { useCallback, useState } from 'react';
import { useTranslations } from 'next-intl';
import { UPLOAD_CONFIG } from '@/shared/config';
import type { ExtendedFile, UploadedFileData } from '@/features/media/types/file-manager.types';
import { mediaFileService } from '@/features/media/services/media-file.service';
import { MultipartUploader } from '@/features/media/services/multipart-uploader';

type TempMetadata = UploadedFileData['metadata'] & { key: string };

const fileIdOf = (file: File) => `${file.name}-${file.size}-${file.lastModified}`;

const generatePreview = async (file: File): Promise<string | undefined> => {
  if (!file.type.startsWith('image/')) {
    return undefined;
  }

  return new Promise((resolve) => {
    const reader = new FileReader();
    reader.onloadend = () => resolve(reader.result as string);
    reader.onerror = () => resolve(undefined);
    reader.readAsDataURL(file);
  });
};

/**
 * Upload dialog state: every selected file goes to the temp bucket right away (multipart for
 * heavy files); committing to the official bucket happens later via `useFileManager`.
 */
export function useTempUpload(currentPath: string) {
  const t = useTranslations('fileManager.dialogs');
  const [uploadedFiles, setUploadedFiles] = useState<UploadedFileData[]>([]);

  const updateFile = useCallback((fileId: string, patch: Partial<UploadedFileData>) => {
    setUploadedFiles((prev) =>
      prev.map((uf) => (fileIdOf(uf.file) === fileId ? { ...uf, ...patch } : uf)),
    );
  }, []);

  const uploadToTemp = useCallback(
    async (file: File, isHeavy: boolean): Promise<TempMetadata> => {
      const fileId = fileIdOf(file);
      if (isHeavy) {
        const uploader = new MultipartUploader(
          file,
          (progress) => updateFile(fileId, { progress: progress.percentage }),
          undefined,
          currentPath,
        );
        return uploader.start();
      }

      const metadata = await mediaFileService.uploadToMinio({
        file,
        onProgress: (percentage) => updateFile(fileId, { progress: percentage }),
      });
      if (!metadata) {
        throw new Error(t('upload.failedToUploadToTemp'));
      }
      return metadata;
    },
    [currentPath, t, updateFile],
  );

  const addFile = useCallback(
    async (file: File) => {
      const fileId = fileIdOf(file);

      // Already uploading or uploaded
      if (uploadedFiles.some((uf) => fileIdOf(uf.file) === fileId)) {
        return;
      }

      setUploadedFiles((prev) => [
        ...prev,
        {
          file,
          key: '',
          uploading: true,
          uploaded: false,
          metadata: {
            original_name: file.name,
            extension: file.name.split('.').pop() || '',
            mime_type: file.type,
            size: file.size,
          },
        },
      ]);

      try {
        const preview = await generatePreview(file);
        const isHeavy = file.size > UPLOAD_CONFIG.HEAVY_FILE_THRESHOLD_BYTES;
        const { key, original_name, extension, mime_type, size } = await uploadToTemp(
          file,
          isHeavy,
        );
        const metadata = { original_name, extension, mime_type, size };

        // Attach the temp upload to the File object for useFileManager
        const extendedFile = file as ExtendedFile;
        if (isHeavy) extendedFile.isHeavyUploaded = true;
        extendedFile.tempKey = key;
        extendedFile.tempMetadata = metadata;

        updateFile(fileId, { key, preview, uploading: false, uploaded: true, metadata });
      } catch (err) {
        console.error(t('upload.autoUploadError'), err);
        const errorMessage = err instanceof Error ? err.message : t('upload.uploadFailed');
        updateFile(fileId, { uploading: false, uploaded: false, error: errorMessage });
      }
    },
    [uploadedFiles, uploadToTemp, updateFile, t],
  );

  const removeFile = useCallback((index: number) => {
    setUploadedFiles((prev) => prev.filter((_, i) => i !== index));
  }, []);

  const reset = useCallback(() => setUploadedFiles([]), []);

  return { uploadedFiles, addFile, removeFile, reset };
}
