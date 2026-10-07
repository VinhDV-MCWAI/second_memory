'use client';

import React, { useReducer, useEffect, useRef, useCallback, useMemo } from 'react';
import { useRouter } from 'next/navigation';
import { authService } from '@/shared/services/modules/auth.service';
import { apiClient } from '@/shared/api/client';
import { ADMIN_ROUTES } from '@/shared/config';
import {
  AuthState,
  AuthAction,
  AuthContextValue,
  LoginCredentials,
  AuthMessage,
  LogoutReason,
  AuthError,
  AUTH_CHANNEL_NAME,
} from '@/shared/types';
import { AuthContext } from './auth-context';

const initialState: AuthState = {
  user: null,
  isAuthenticated: false,
  isLoading: true,
  error: null,
};

function authReducer(state: AuthState, action: AuthAction): AuthState {
  switch (action.type) {
    case 'SET_LOADING':
      return { ...state, isLoading: action.payload };
    case 'SET_AUTHENTICATED':
      return { user: action.payload, isAuthenticated: true, isLoading: false, error: null };
    case 'SET_ERROR':
      return { ...state, error: action.payload, isLoading: false };
    case 'CLEAR_ERROR':
      return { ...state, error: null };
    case 'LOGOUT':
      return { ...initialState, isLoading: false };
    default:
      return state;
  }
}

/**
 * Session auth (Sanctum SPA cookie, ADR-0004): the server owns the session, so there is
 * no token to refresh. A 401 from any API call means the session is gone.
 */
export function AuthProvider({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const [state, dispatch] = useReducer(authReducer, initialState);
  const channelRef = useRef<BroadcastChannel | null>(null);

  const broadcast = useCallback((message: AuthMessage) => {
    channelRef.current?.postMessage(message);
  }, []);

  const handleLogoutSync = useCallback(() => {
    dispatch({ type: 'LOGOUT' });
    router.push(ADMIN_ROUTES.LOGIN);
  }, [router]);

  const performLogout = useCallback(
    async (reason: LogoutReason = 'manual') => {
      try {
        await authService.logout();
      } catch {
        // Logout is idempotent on the server; the local state is cleared anyway
      } finally {
        handleLogoutSync();
        broadcast({ type: 'LOGOUT', payload: { reason } });
      }
    },
    [handleLogoutSync, broadcast],
  );

  useEffect(() => {
    if (typeof window === 'undefined') return;

    const channel = new BroadcastChannel(AUTH_CHANNEL_NAME);
    channelRef.current = channel;

    channel.onmessage = (event: MessageEvent<AuthMessage>) => {
      const message = event.data;
      if (message.type === 'LOGIN_SUCCESS') {
        dispatch({ type: 'SET_AUTHENTICATED', payload: message.payload.user });
      } else if (message.type === 'LOGOUT') {
        handleLogoutSync();
      }
    };

    return () => {
      channel.close();
      channelRef.current = null;
    };
  }, [handleLogoutSync]);

  useEffect(() => {
    apiClient.onUnauthorized(() => {
      handleLogoutSync();
      broadcast({ type: 'LOGOUT', payload: { reason: 'session_expired' } });
    });
    return () => apiClient.onUnauthorized(null);
  }, [handleLogoutSync, broadcast]);

  const login = useCallback(
    async (credentials: LoginCredentials) => {
      dispatch({ type: 'SET_LOADING', payload: true });
      dispatch({ type: 'CLEAR_ERROR' });

      try {
        const user = await authService.login(credentials);
        dispatch({ type: 'SET_AUTHENTICATED', payload: user });
        broadcast({ type: 'LOGIN_SUCCESS', payload: { user } });
      } catch (error: unknown) {
        const authError: AuthError = {
          message: (error as Error).message || 'auth.invalidCredentials',
          code: (error as { code?: string }).code || 'LOGIN_FAILED',
          status: (error as { status?: number }).status,
        };
        dispatch({ type: 'SET_ERROR', payload: authError });
        throw error;
      }
    },
    [broadcast],
  );

  const clearError = useCallback(() => {
    dispatch({ type: 'CLEAR_ERROR' });
  }, []);

  useEffect(() => {
    const controller = new AbortController();

    authService
      .getMe({ signal: controller.signal })
      .then((user) => {
        if (!controller.signal.aborted) dispatch({ type: 'SET_AUTHENTICATED', payload: user });
      })
      .catch(() => {
        if (!controller.signal.aborted) dispatch({ type: 'SET_LOADING', payload: false });
      });

    return () => controller.abort();
  }, []);

  const contextValue: AuthContextValue = useMemo(
    () => ({
      user: state.user,
      isAuthenticated: state.isAuthenticated,
      isLoading: state.isLoading,
      error: state.error,
      login,
      logout: performLogout,
      clearError,
    }),
    [
      state.user,
      state.isAuthenticated,
      state.isLoading,
      state.error,
      login,
      performLogout,
      clearError,
    ],
  );

  return <AuthContext.Provider value={contextValue}>{children}</AuthContext.Provider>;
}
