'use client';

import { useEffect } from 'react';
import { useTranslations } from 'next-intl';
import { useAuth } from '@/shared/hooks';
import { BackgroundTaskStatus } from '@/shared/enums';
import { disconnectEcho, getEcho } from '@/shared/realtime/echo';
import { notification } from '@/shared/utils';
import type { BackgroundTask } from '@/shared/types/models';
import {
  ADMIN_CHANNEL_PREFIX,
  announceFinished,
  BACKGROUND_TASK_FINISHED_EVENT,
  fetchTaskStatus,
  isFinished,
} from '../lib/background-tasks';
import { pendingTasks } from '../lib/pending-tasks';

/**
 * Shows a toast when one of the admin's background tasks finishes (ADR-0014). Listens on the admin's
 * private channel; once subscribed it also asks the API about tasks this tab is still waiting for,
 * because an event sent while no page was listening (reload, slow connection) is lost.
 */
export function BackgroundTaskListener() {
  const { user } = useAuth();
  const t = useTranslations('backgroundTasks');
  const adminId = user?.id;

  useEffect(() => {
    const echo = getEcho();
    if (adminId === undefined || !echo) return;

    const announced = new Set<string>();
    const announce = (task: BackgroundTask) => {
      pendingTasks.remove(task.id);
      if (announced.has(task.id)) return;
      announced.add(task.id);
      announceFinished(task);
      if (task.status === BackgroundTaskStatus.COMPLETED) {
        notification.success(task.message ?? t('completed'));
      } else {
        notification.error(task.message ?? t('failed'));
      }
    };

    const channelName = `${ADMIN_CHANNEL_PREFIX}${adminId}`;
    echo
      .private(channelName)
      .listen(`.${BACKGROUND_TASK_FINISHED_EVENT}`, announce)
      .subscribed(() => {
        for (const id of pendingTasks.list()) {
          fetchTaskStatus(id)
            .then((task) => {
              if (task === null) pendingTasks.remove(id);
              else if (isFinished(task)) announce(task);
            })
            .catch(() => undefined); // Network hiccup: the next subscription asks again
        }
      });

    return () => {
      echo.leave(channelName);
      disconnectEcho();
    };
  }, [adminId, t]);

  return null;
}
