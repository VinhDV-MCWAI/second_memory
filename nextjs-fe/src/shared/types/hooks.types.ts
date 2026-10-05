/**
 * Hooks Types
 */

export interface UseActionLockOptions {
  delay?: number;
}

export interface UseActionLockReturn {
  isLoading: boolean;
  execute: <T>(action: () => Promise<T>) => Promise<T | undefined>;
}

export interface GlobalLoadingContextType {
  showGlobalLoading: (message?: string) => void;
  hideGlobalLoading: () => void;
  isLoading: boolean;
}
