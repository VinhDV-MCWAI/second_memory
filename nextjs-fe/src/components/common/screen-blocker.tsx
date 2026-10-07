import React from 'react';
import { createPortal } from 'react-dom';
import { LoadingSpinner } from '@/components/ui/loading';
import { useTranslations } from 'next-intl';
import type { ScreenBlockerProps } from '@/shared/types';

export function ScreenBlocker({ isVisible, message }: ScreenBlockerProps) {
  const t = useTranslations('common');
  const [mounted, setMounted] = React.useState(false);

  React.useEffect(() => {
    setMounted(true);
    return () => setMounted(false);
  }, []);

  if (!mounted || !isVisible) return null;

  return createPortal(
    <div className="pointer-events-auto fixed inset-0 z-[9999] flex cursor-not-allowed items-center justify-center bg-black/50 backdrop-blur-sm">
      <div className="flex min-w-[200px] flex-col items-center gap-4 rounded-lg bg-white p-6 shadow-xl dark:bg-slate-900">
        <LoadingSpinner size="lg" />
        <p className="text-lg font-medium text-slate-900 dark:text-white">
          {message || t('processing')}
        </p>
      </div>
    </div>,
    document.body,
  );
}
