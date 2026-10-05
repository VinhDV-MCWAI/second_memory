'use client';

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useTranslations } from 'next-intl';
import { ADMIN_ROUTES } from '@/shared/config';

export default function Home() {
  const router = useRouter();
  const t = useTranslations('common');

  useEffect(() => {
    // Redirect root to admin
    router.push(ADMIN_ROUTES.DASHBOARD);
  }, [router]);

  return (
    <div className="flex min-h-screen items-center justify-center bg-zinc-50 font-sans dark:bg-black">
      <div>
        <h1 className="text-2xl">{t('redirecting')}</h1>
      </div>
    </div>
  );
}