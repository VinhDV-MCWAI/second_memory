import axios from 'axios';
import { apiClient, API_ENDPOINTS } from '@/shared/api';
import { BackgroundTaskStatus } from '@/shared/enums';
import type { BackgroundTask } from '@/shared/types/models';

/** Private channel of one admin and the event name the API broadcasts on it (ADR-0014). */
export const ADMIN_CHANNEL_PREFIX = 'admin.';
export const BACKGROUND_TASK_FINISHED_EVENT = 'background-task.finished';

const BASE = API_ENDPOINTS.SYSTEM.BACKGROUND_TASK;

export function isFinished(task: BackgroundTask): boolean {
  return (
    task.status === BackgroundTaskStatus.COMPLETED || task.status === BackgroundTaskStatus.FAILED
  );
}

/** Queues the test job; it finishes (or fails on purpose) after a few seconds. */
export async function startTestTask(fail: boolean): Promise<BackgroundTask> {
  const response = await apiClient.post<{ data: BackgroundTask }>(`${BASE}/test`, { fail });
  return response.data.data;
}

/** The task's status, or null when the API no longer knows it (expired after a day, or not yours). */
export async function fetchTaskStatus(id: string): Promise<BackgroundTask | null> {
  try {
    const response = await apiClient.get<{ data: BackgroundTask }>(`${BASE}/status/${id}`);
    return response.data.data;
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 404) return null;
    throw error;
  }
}
