'use client';

import Link from 'next/link';
import { useTranslations } from 'next-intl';
import { Button } from '@/components/ui/button';
import { ShieldAlert, ArrowLeft } from 'lucide-react';
import { SUPPORT_EMAIL } from '@/shared/config';

export default function UnauthorizedPage() {
  const t = useTranslations('errors');
  const tCommon = useTranslations('common');

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-950">
      <div className="mx-auto max-w-2xl px-4 text-center">
        {/* Icon */}
        <div className="mb-8 flex justify-center">
          <div className="rounded-full bg-amber-100 p-6 dark:bg-amber-900/20">
            <ShieldAlert className="h-16 w-16 text-amber-600 dark:text-amber-400" />
          </div>
        </div>

        {/* Error Code */}
        <h1 className="mb-4 text-8xl font-bold text-slate-900 dark:text-white">
          401
        </h1>

        {/* Title */}
        <h2 className="mb-3 text-2xl font-semibold text-slate-900 dark:text-slate-100">
          {t('E0401')}
        </h2>

        {/* Description */}
        <p className="mb-6 text-slate-600 dark:text-slate-400">
          {t('unauthorizedDescription')}
        </p>

        {/* Actions */}
        <div className="flex flex-col gap-3 sm:flex-row sm:justify-center">
          <Button onClick={() => window.history.back()} variant="outline" size="lg" className="gap-2">
            <ArrowLeft className="h-4 w-4" />
            {tCommon('goBack')}
          </Button>
          <Button asChild size="lg" className="gap-2">
            <Link href="/login">
              {tCommon('signIn')}
            </Link>
          </Button>
        </div>

        {/* Support Link */}
        <p className="mt-8 text-sm text-slate-500 dark:text-slate-400">
          {t('needHelp')}{' '}
          <a href={`mailto:${SUPPORT_EMAIL}`} className="text-blue-600 hover:underline dark:text-blue-400">
            {t('support')}
          </a>
        </p>
      </div>
    </div>
  );
}
