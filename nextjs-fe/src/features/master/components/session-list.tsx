'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { LogOut, Monitor } from 'lucide-react';
import { DataTable, type Column } from '@/components/common/data-table/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { getApiErrorMessage, notification } from '@/shared/utils';
import { formatTimestamp } from '@/shared/utils/date-formatter';
import type { AdminSession } from '@/shared/types/models';
import { useAdminSessionActions, useAdminSessions } from '../hooks/use-admin-sessions';
import { describeUserAgent } from '../lib/user-agent';

/**
 * Sessions screen (AUTH-04): the devices the signed-in admin is signed in on. Any device but this one
 * can be signed out; this one signs out with the usual Logout.
 */
export function SessionList() {
  const t = useTranslations('sessions');
  const tCommon = useTranslations('common');
  const { data = [], isLoading } = useAdminSessions();
  const { end, endOthers } = useAdminSessionActions();
  const [target, setTarget] = useState<AdminSession | null>(null);
  const [endingOthers, setEndingOthers] = useState(false);

  const deviceName = (session: AdminSession) => {
    const { browser, os } = describeUserAgent(session.user_agent);
    if (browser && os) return t('deviceName', { browser, os });
    return browser ?? os ?? t('unknownDevice');
  };

  const run = async (action: () => Promise<number>, close: () => void) => {
    try {
      notification.success(t('signedOut', { count: await action() }));
      close();
    } catch (error) {
      notification.error(getApiErrorMessage(error) ?? t('failed'));
    }
  };

  const columns: Column<AdminSession>[] = [
    {
      key: 'device',
      label: t('device'),
      render: (session) => (
        <span className="flex items-center gap-2" title={session.user_agent ?? undefined}>
          <Monitor className="h-4 w-4 shrink-0 text-slate-400" aria-hidden />
          <span>{deviceName(session)}</span>
          {session.is_current && <Badge variant="secondary">{t('thisDevice')}</Badge>}
        </span>
      ),
    },
    { key: 'ip_address', label: t('ipAddress'), render: (session) => session.ip_address ?? '' },
    {
      key: 'signed_in_at',
      label: t('signedIn'),
      render: (session) => formatTimestamp(session.signed_in_at),
    },
    {
      key: 'last_used_at',
      label: t('lastActive'),
      render: (session) => formatTimestamp(session.last_used_at),
    },
    {
      key: 'actions',
      label: tCommon('actions'),
      render: (session) =>
        !session.is_current && (
          <Button variant="outline" size="sm" onClick={() => setTarget(session)}>
            <LogOut className="mr-1 h-3 w-3" />
            {t('signOut')}
          </Button>
        ),
    },
  ];

  const others = data.filter((session) => !session.is_current).length;

  return (
    <div className="mt-6 space-y-4">
      <div className="flex justify-end">
        {others > 0 && (
          <Button variant="destructive" onClick={() => setEndingOthers(true)}>
            <LogOut className="mr-2 h-4 w-4" />
            {t('signOutOthers')}
          </Button>
        )}
      </div>

      <DataTable data={data} columns={columns} loading={isLoading} showActions={false} />
      {!isLoading && others === 0 && (
        <p className="py-4 text-center text-sm text-muted-foreground">{t('onlyThisDevice')}</p>
      )}

      <ConfirmDialog
        open={target !== null}
        onOpenChange={(open) => !open && setTarget(null)}
        title={t('signOutTitle')}
        description={target ? t('signOutDescription', { device: deviceName(target) }) : ''}
        confirmText={t('signOut')}
        variant="destructive"
        isLoading={end.isPending}
        onConfirm={() =>
          target
            ? run(
                () => end.mutateAsync([target.id]),
                () => setTarget(null),
              )
            : undefined
        }
      />
      <ConfirmDialog
        open={endingOthers}
        onOpenChange={setEndingOthers}
        title={t('signOutOthersTitle')}
        description={t('signOutOthersDescription')}
        confirmText={t('signOutOthers')}
        variant="destructive"
        isLoading={endOthers.isPending}
        onConfirm={() =>
          run(
            () => endOthers.mutateAsync(),
            () => setEndingOthers(false),
          )
        }
      />
    </div>
  );
}
