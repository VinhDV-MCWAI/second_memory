import React, { useRef, useState, useCallback } from 'react';
import type { UseActionLockOptions, UseActionLockReturn } from '@/shared/types';

export function useActionLock(options: UseActionLockOptions = {}): UseActionLockReturn {
  const { delay = 0 } = options;
  const [isLoading, setIsLoading] = useState(false);
  const isLocked = useRef(false);
  const isMounted = useRef(true);

  // Track mount status to prevent state updates on unmounted component
  React.useEffect(() => {
    isMounted.current = true;
    return () => {
      isMounted.current = false;
    };
  }, []);

  const execute = useCallback(async <T>(action: () => Promise<T>): Promise<T | undefined> => {
    if (isLocked.current) {
      return undefined;
    }

    isLocked.current = true;
    if (isMounted.current) setIsLoading(true);

    try {
      return await action();
    } finally {
      if (delay > 0 && isMounted.current) {
        // Keep locked for a cooldown period
        setTimeout(() => {
            isLocked.current = false;
            // Only update state if still mounted
            if (isMounted.current) {
                setIsLoading(false);
            }
        }, delay);
      } else {
        isLocked.current = false;
        if (isMounted.current) setIsLoading(false);
      }
    }
  }, [delay]);

  return { isLoading, execute };
}
