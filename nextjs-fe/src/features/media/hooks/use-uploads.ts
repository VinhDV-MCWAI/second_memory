'use client';

import { useEffect, useRef, useState } from 'react';
import { BackgroundTaskStatus } from '@/shared/enums';
import { getApiErrorMessage } from '@/shared/utils';
import { onBackgroundTaskFinished } from '@/features/system/lib/background-tasks';
import { pendingTasks } from '@/features/system/lib/pending-tasks';
import { uploadFile } from '../lib/uploader';

/** Background task type of the post-upload processing (laravel-api MediaConst::TASK_PROCESS). */
export const MEDIA_PROCESS_TASK = 'media.process';

export type UploadState = 'waiting' | 'uploading' | 'processing' | 'done' | 'failed' | 'cancelled';

export interface UploadItem {
  key: string;
  name: string;
  folder: string;
  size: number;
  loaded: number;
  state: UploadState;
  message?: string;
  taskId?: string;
  /** Multipart upload id: set when resuming, or once a large upload has started. */
  uploadId?: number;
}

export interface UploadRequest {
  file: File;
  /** An unfinished upload of this same file (`media/multipart/unfinished`) to continue. */
  resumeId?: number;
}

const FINISHED: UploadState[] = ['done', 'failed', 'cancelled'];

/**
 * Upload queue of the file screen: one file at a time (a large file already sends 4 parts at once).
 * A large file ends "processing" until its background task reports back. `onSettled` runs whenever
 * an upload stops (done, failed, cancelled), so the list of unfinished uploads can refresh.
 */
export function useUploads(onFileReady: () => void, onSettled: () => void = () => undefined) {
  const [items, setItems] = useState<UploadItem[]>([]);
  const controllers = useRef(new Map<string, AbortController>());
  const queue = useRef<Promise<void>>(Promise.resolve());
  const onReady = useRef(onFileReady);
  const onStop = useRef(onSettled);

  useEffect(() => {
    onReady.current = onFileReady;
    onStop.current = onSettled;
  });

  const patch = (key: string, changes: Partial<UploadItem>) =>
    setItems((previous) =>
      previous.map((item) => (item.key === key ? { ...item, ...changes } : item)),
    );

  useEffect(
    () =>
      onBackgroundTaskFinished((task) => {
        if (task.type !== MEDIA_PROCESS_TASK) return;
        setItems((previous) =>
          previous.map((item) =>
            item.taskId === task.id
              ? {
                  ...item,
                  state: task.status === BackgroundTaskStatus.COMPLETED ? 'done' : 'failed',
                  message: task.message ?? undefined,
                }
              : item,
          ),
        );
        onReady.current();
      }),
    [],
  );

  const run = async (item: UploadItem, file: File) => {
    const controller = controllers.current.get(item.key);
    if (!controller || controller.signal.aborted) return;
    patch(item.key, { state: 'uploading' });
    try {
      const result = await uploadFile(file, {
        folder: item.folder,
        signal: controller.signal,
        resumeId: item.uploadId,
        onStarted: (uploadId) => patch(item.key, { uploadId }),
        onProgress: (loaded) => patch(item.key, { loaded }),
      });
      if (result.kind === 'completed') {
        patch(item.key, { state: 'done', loaded: item.size });
        onReady.current();
      } else {
        pendingTasks.add(result.task.id);
        patch(item.key, { state: 'processing', loaded: item.size, taskId: result.task.id });
      }
    } catch (error) {
      patch(
        item.key,
        controller.signal.aborted
          ? { state: 'cancelled' }
          : {
              state: 'failed',
              message:
                getApiErrorMessage(error) ?? (error instanceof Error ? error.message : undefined),
            },
      );
    } finally {
      controllers.current.delete(item.key);
      onStop.current();
    }
  };

  const add = (requests: UploadRequest[], folder: string) => {
    const added = requests.map(({ file, resumeId }) => {
      const item: UploadItem = {
        key: crypto.randomUUID(),
        name: file.name,
        folder,
        size: file.size,
        loaded: 0,
        state: 'waiting',
        uploadId: resumeId,
      };
      controllers.current.set(item.key, new AbortController());
      queue.current = queue.current.then(() => run(item, file));
      return item;
    });
    setItems((previous) => [...previous, ...added]);
  };

  const cancel = (key: string) => {
    const controller = controllers.current.get(key);
    controller?.abort();
    setItems((previous) =>
      previous.map((item) =>
        item.key === key && item.state === 'waiting' ? { ...item, state: 'cancelled' } : item,
      ),
    );
  };

  const clearFinished = () =>
    setItems((previous) => previous.filter((item) => !FINISHED.includes(item.state)));

  return { items, add, cancel, clearFinished };
}
