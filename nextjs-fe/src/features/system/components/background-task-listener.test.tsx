import { beforeEach, describe, expect, it, vi } from 'vitest';
import { render, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { apiUrl, envelope, errorEnvelope, server } from '@/test/server';
import { BackgroundTaskStatus } from '@/shared/enums';
import type { BackgroundTask } from '@/shared/types/models';
import { BackgroundTaskListener } from './background-task-listener';
import { pendingTasks } from '../lib/pending-tasks';

const notify = vi.hoisted(() => ({ success: vi.fn(), error: vi.fn() }));
const channel = vi.hoisted(() => {
  const handlers: { event?: (task: unknown) => void; subscribed?: () => void } = {};
  const api = {
    handlers,
    listen: vi.fn((_: string, handler: (task: unknown) => void) => {
      handlers.event = handler;
      return api;
    }),
    subscribed: vi.fn((handler: () => void) => {
      handlers.subscribed = handler;
      return api;
    }),
  };
  return api;
});
const echo = vi.hoisted(() => ({ private: vi.fn(() => channel), leave: vi.fn() }));

vi.mock('next-intl', () => ({ useTranslations: () => (key: string) => key }));
vi.mock('@/shared/hooks', () => ({ useAuth: () => ({ user: { id: 7 } }) }));
vi.mock('@/shared/realtime/echo', () => ({ getEcho: () => echo, disconnectEcho: vi.fn() }));
vi.mock('@/shared/utils', () => ({ notification: notify }));

const task = (overrides: Partial<BackgroundTask> = {}): BackgroundTask => ({
  id: 'task-1',
  type: 'system.test',
  subject_id: null,
  status: BackgroundTaskStatus.COMPLETED,
  message: 'Test background job finished after 3s',
  updated_at: '2026-10-13T00:00:00+00:00',
  ...overrides,
});

describe('BackgroundTaskListener', () => {
  beforeEach(() => {
    window.sessionStorage.clear();
    vi.clearAllMocks();
  });

  it('listens on the admin channel and shows the result of a finished task once', () => {
    render(<BackgroundTaskListener />);

    expect(echo.private).toHaveBeenCalledWith('admin.7');
    expect(channel.listen).toHaveBeenCalledWith('.background-task.finished', expect.any(Function));

    channel.handlers.event?.(task());
    channel.handlers.event?.(task());
    channel.handlers.event?.(
      task({ id: 'task-2', status: BackgroundTaskStatus.FAILED, message: null }),
    );

    expect(notify.success).toHaveBeenCalledOnce();
    expect(notify.success).toHaveBeenCalledWith('Test background job finished after 3s');
    expect(notify.error).toHaveBeenCalledWith('failed');
  });

  it('asks the API about tasks it missed while not listening', async () => {
    pendingTasks.add('task-1');
    pendingTasks.add('gone');
    pendingTasks.add('still-running');
    server.use(
      http.get(apiUrl('/admin/background-task/status/task-1'), () =>
        HttpResponse.json(envelope({ data: task() })),
      ),
      http.get(apiUrl('/admin/background-task/status/gone'), () =>
        HttpResponse.json(errorEnvelope(404, 'Not Found'), { status: 404 }),
      ),
      http.get(apiUrl('/admin/background-task/status/still-running'), () =>
        HttpResponse.json(
          envelope({ data: task({ id: 'still-running', status: BackgroundTaskStatus.RUNNING }) }),
        ),
      ),
    );

    render(<BackgroundTaskListener />);
    channel.handlers.subscribed?.();

    await waitFor(() => expect(notify.success).toHaveBeenCalledOnce());
    await waitFor(() => expect(pendingTasks.list()).toEqual(['still-running']));
  });

  it('leaves the channel when unmounted', () => {
    const { unmount } = render(<BackgroundTaskListener />);
    unmount();

    expect(echo.leave).toHaveBeenCalledWith('admin.7');
  });
});
