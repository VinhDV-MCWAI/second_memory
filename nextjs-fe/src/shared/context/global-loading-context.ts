'use client';

import { createContext, useContext } from 'react';
import type { GlobalLoadingContextType } from '@/shared/types';

export const GlobalLoadingContext = createContext<GlobalLoadingContextType>({
  showGlobalLoading: () => {},
  hideGlobalLoading: () => {},
  isLoading: false,
});

export const useGlobalLoading = () => useContext(GlobalLoadingContext);
