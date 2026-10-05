'use client';

import { createContext, useContext, ReactNode } from 'react';
import type { FileManagerContextType } from '@/shared/types/file-manager.types';
import { useFileManager as useFileManagerLogic } from '@/shared/hooks/use-file-manager';


const FileManagerContext = createContext<FileManagerContextType | undefined>(undefined);

export const FileManagerProvider = ({ children }: { children: ReactNode }) => {
  const fileManager = useFileManagerLogic();

  return (
    <FileManagerContext.Provider value={fileManager}>
      {children}
    </FileManagerContext.Provider>
  );
};

export const useFileManager = () => {
  const context = useContext(FileManagerContext);
  if (context === undefined) {
    throw new Error('hooks.useFileManagerError');
  }
  return context;
};
