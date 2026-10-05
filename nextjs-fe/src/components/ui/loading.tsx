'use client';

import type { LoadingSpinnerProps, LoadingOverlayProps, LoadingSkeletonProps } from '@/shared/types';
import { useTranslations } from 'next-intl';
import { UI_CONSTANTS } from '@/shared/config';

export function LoadingSpinner({ size = 'md' }: LoadingSpinnerProps) {
  const sizeClasses = {
    sm: 'h-4 w-4',
    md: 'h-8 w-8',
    lg: 'h-12 w-12',
  };

  return (
    <div
      className={`${sizeClasses[size]} animate-spin rounded-full border-2 border-blue-600 border-t-transparent`}
    />
  );
}

export function LoadingOverlay({ message, variant = 'fixed' }: LoadingOverlayProps & { variant?: 'fixed' | 'absolute' }) {
  const t = useTranslations('common');
  
  const positionClass = variant === 'fixed' ? 'fixed inset-0 z-50' : 'absolute inset-0 z-10 rounded-[inherit]';

  return (
    <div className={`${positionClass} bg-black/50 flex items-center justify-center backdrop-blur-[1px]`}>
      <div className="bg-white dark:bg-slate-900 rounded-lg p-6 shadow-xl">
        <div className="flex flex-col items-center gap-4">
          <LoadingSpinner size="lg" />
          <p className="text-slate-900 dark:text-white font-medium">{message || t('loading')}</p>
        </div>
      </div>
    </div>
  );
}

export function LoadingPage() {
  const t = useTranslations('common');
  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-950">
      <div className="text-center">
        <LoadingSpinner size="lg" />
        <p className="mt-4 text-slate-600 dark:text-slate-400">{t('loading')}</p>
      </div>
    </div>
  );
}

export function LoadingSkeleton({ rows = UI_CONSTANTS.DEFAULT_SKELETON_ROWS }: LoadingSkeletonProps) {
  return (
    <div className="space-y-3">
      {Array.from({ length: rows }).map((_, i) => (
        <div
          key={i}
          className="h-12 bg-slate-200 dark:bg-slate-800 rounded animate-pulse"
        />
      ))}
    </div>
  );
}
