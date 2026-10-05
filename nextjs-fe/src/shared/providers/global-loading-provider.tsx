'use client';

import React, { useState, useMemo, useCallback } from 'react';
import { GlobalLoadingContext } from '@/shared/context/global-loading-context';
import { ScreenBlocker } from '@/components/common/screen-blocker';

export function GlobalLoadingProvider({ children }: { children: React.ReactNode }) {
  const [isLoading, setIsLoading] = useState(false);
  const [message, setMessage] = useState<string | undefined>(undefined);

  const showGlobalLoading = useCallback((msg?: string) => {
    setMessage(msg);
    setIsLoading(true);
  }, []);

  const hideGlobalLoading = useCallback(() => {
    setIsLoading(false);
    setMessage(undefined);
  }, []);

  const value = useMemo(
    () => ({
      isLoading,
      showGlobalLoading,
      hideGlobalLoading,
    }),
    [isLoading, showGlobalLoading, hideGlobalLoading]
  );

  return (
    <GlobalLoadingContext.Provider value={value}>
      {children}
      <ScreenBlocker isVisible={isLoading} message={message} />
    </GlobalLoadingContext.Provider>
  );
}
