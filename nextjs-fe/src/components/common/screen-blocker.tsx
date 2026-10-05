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
    <div className="fixed inset-0 z-[9999] bg-black/50 backdrop-blur-sm flex items-center justify-center pointer-events-auto cursor-not-allowed">
      <div className="bg-white dark:bg-slate-900 rounded-lg p-6 shadow-xl flex flex-col items-center gap-4 min-w-[200px]">
        <LoadingSpinner size="lg" />
        <p className="text-slate-900 dark:text-white font-medium text-lg">
          {message || t('processing')}
        </p>
      </div>
    </div>,
    document.body
  );
}
