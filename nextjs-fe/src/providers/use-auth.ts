import { useContext } from 'react';
import { AuthContext } from './auth-context';
import { AuthContextValue } from '@/shared/types';

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);

  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }

  return context;
}
