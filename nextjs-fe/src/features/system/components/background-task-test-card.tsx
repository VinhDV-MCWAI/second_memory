'use client';

import { useMutation } from '@tanstack/react-query';
import { useTranslations } from 'next-intl';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { usePermissions } from '@/shared/hooks';
import { getApiErrorMessage, notification } from '@/shared/utils';
import { startTestTask } from '../lib/background-tasks';
import { pendingTasks } from '../lib/pending-tasks';

/**
 * Runs the test job to check the whole chain: queue → worker → Reverb → this browser (ADR-0014).
 * The result arrives as a toast from BackgroundTaskListener.
 */
export function BackgroundTaskTestCard() {
  const t = useTranslations('backgroundTasks');
  const { can } = usePermissions();
  const start = useMutation({
    mutationFn: startTestTask,
    onSuccess: (task) => {
      pendingTasks.add(task.id);
      notification.success(t('queued'));
    },
    onError: (error) => notification.error(getApiErrorMessage(error) ?? t('failed')),
  });

  if (!can('background-task.test')) return null;

  return (
    <Card className="p-6">
      <h3 className="text-lg font-semibold">{t('title')}</h3>
      <p className="mt-2 text-sm text-muted-foreground">{t('description')}</p>
      <div className="mt-4 flex flex-wrap gap-2">
        <Button onClick={() => start.mutate(false)} disabled={start.isPending}>
          {t('runTest')}
        </Button>
        <Button variant="outline" onClick={() => start.mutate(true)} disabled={start.isPending}>
          {t('runFailing')}
        </Button>
      </div>
    </Card>
  );
}
