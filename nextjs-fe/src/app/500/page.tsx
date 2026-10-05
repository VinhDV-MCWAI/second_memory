'use client';

import Link from 'next/link';
import { useTranslations } from 'next-intl';
import { Button } from '@/components/ui/button';
import { ServerCrash, Home, RotateCcw } from 'lucide-react';
import { SUPPORT_EMAIL } from '@/shared/config';

export default function ServerErrorPage() {
  const t = useTranslations('errors');
  const tCommon = useTranslations('common');

  const handleRetry = () => {
    window.location.reload();
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-950">
      <div className="mx-auto max-w-2xl px-4 text-center">
        {/* Icon */}
        <div className="mb-8 flex justify-center">
          <div className="rounded-full bg-red-100 p-6 dark:bg-red-900/20">
            <ServerCrash className="h-16 w-16 text-red-600 dark:text-red-400" />
          </div>
        </div>

        {/* Error Code */}
        <h1 className="mb-4 text-8xl font-bold text-slate-900 dark:text-white">
          500
        </h1>

        {/* Title */}
        <h2 className="mb-3 text-2xl font-semibold text-slate-900 dark:text-slate-100">
          {t('internalServerError')}
        </h2>

        {/* Description */}
        <p className="mb-6 text-slate-600 dark:text-slate-400">
          {t('internalServerErrorDescription')}
        </p>

        {/* Actions */}
        <div className="flex flex-col gap-3 sm:flex-row sm:justify-center">
          <Button onClick={handleRetry} size="lg" className="gap-2">
            <RotateCcw className="h-4 w-4" />
            {tCommon('tryAgain')}
          </Button>
          <Button variant="outline" asChild size="lg" className="gap-2">
            <Link href="/admin">
              <Home className="h-4 w-4" />
              {tCommon('backToDashboard')}
            </Link>
          </Button>
        </div>

        {/* Support Link */}
        <div className="mt-12 text-sm text-slate-500 dark:text-slate-500">
          {t('stillExperiencingIssues')}{' '}
          <a
            href={`mailto:${SUPPORT_EMAIL}`}
            className="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
          >
            {tCommon('contactSupport')}
          </a>
        </div>
      </div>
    </div>
  );
}
