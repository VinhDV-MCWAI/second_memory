'use client';

import React, { useReducer, useEffect, useRef, useCallback, useMemo } from 'react';
import { useRouter } from 'next/navigation';
import { authService } from '@/shared/services/modules/auth.service';
import { ADMIN_ROUTES } from '@/shared/config/constant';
import {
  AuthState,
  AuthAction,
  AuthContextValue,
  LoginCredentials,
  AuthMessage,
  LoginSuccessPayload,
  RefreshSuccessPayload,
  LogoutReason,
  AuthError,
  User,
  AUTH_CHANNEL_NAME,
  DEFAULT_REFRESH_CONFIG,
} from '@/shared/types';
import { AuthContext } from './auth-context';

// Broadcast event types
const BROADCAST_EVENTS = {
  LOGIN_SUCCESS: 'LOGIN_SUCCESS',
  REFRESH_SUCCESS: 'REFRESH_SUCCESS',
  LOGOUT: 'LOGOUT',
  FORCE_REFRESH: 'FORCE_REFRESH',
} as const;

const initialState: AuthState = {
  user: null,
  isAuthenticated: false,
  isLoading: true,
  expiresAt: null,
  error: null,
};

function authReducer(state: AuthState, action: AuthAction): AuthState {
  switch (action.type) {
    case 'SET_LOADING':
      return { ...state, isLoading: action.payload };
    case 'SET_USER':
      return { ...state, user: action.payload };
    case 'SET_EXPIRES_AT':
      return { ...state, expiresAt: action.payload };
    case 'SET_AUTHENTICATED':
      return {
        ...state,
        user: action.payload.user,
        expiresAt: action.payload.expiresAt,
        isAuthenticated: true,
        isLoading: false,
        error: null,
      };
    case 'SET_ERROR':
      return { ...state, error: action.payload, isLoading: false };
    case 'CLEAR_ERROR':
      return { ...state, error: null };
    case 'LOGOUT':
      return { ...initialState, isLoading: false };
    case 'RESET':
      return initialState;
    default:
      return state;
  }
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const [state, dispatch] = useReducer(authReducer, initialState);
  const channelRef = useRef<BroadcastChannel | null>(null);
  const timerRef = useRef<NodeJS.Timeout | null>(null);
  const retryCountRef = useRef(0);

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
        // Ignore logout errors
      } finally {
        handleLogoutSync();

        if (typeof window !== 'undefined') {
          const channel = new BroadcastChannel(AUTH_CHANNEL_NAME);
          channel.postMessage({ type: BROADCAST_EVENTS.LOGOUT, payload: { reason } });
          channel.close();
        }
      }
    },
    [handleLogoutSync]
  );

  const clearTimer = useCallback(() => {
    if (timerRef.current) {
      clearTimeout(timerRef.current);
      timerRef.current = null;
    }
  }, []);

  const performRefresh = useCallback(
    async (isRetry = false) => {
      if (authService.isRefreshLocked()) return;

      const acquired = await authService.acquireRefreshLock();
      if (!acquired) return;

      try {
        const data = await authService.refreshToken();
        retryCountRef.current = 0;
        dispatch({ type: 'SET_EXPIRES_AT', payload: data.expires_at });
        broadcast({ type: BROADCAST_EVENTS.REFRESH_SUCCESS, payload: { expiresAt: data.expires_at } });
      } catch {
        if (!isRetry && retryCountRef.current < DEFAULT_REFRESH_CONFIG.maxRetries!) {
          retryCountRef.current++;
          setTimeout(() => performRefresh(true), DEFAULT_REFRESH_CONFIG.retryDelay!);
        } else {
          const authError: AuthError = {
            message: 'errors.E0002',
            code: 'TOKEN_REFRESH_FAILED',
          };
          dispatch({ type: 'SET_ERROR', payload: authError });
          await performLogout('token_refresh_failed');
        }
      } finally {
        authService.releaseRefreshLock();
      }
    },
    [broadcast, performLogout]
  );

  const scheduleRefresh = useCallback(() => {
    clearTimer();

    if (!state.expiresAt || !state.isAuthenticated) return;

    const now = Date.now();
    const expirationTime = state.expiresAt * 1000;
    const timeUntilRefresh = expirationTime - now - DEFAULT_REFRESH_CONFIG.refreshBeforeExpiry!;

    if (timeUntilRefresh <= 0) {
      performRefresh();
    } else {
      timerRef.current = setTimeout(performRefresh, timeUntilRefresh);
    }
  }, [state.expiresAt, state.isAuthenticated, performRefresh, clearTimer]);

  useEffect(() => {
    scheduleRefresh();
    return clearTimer;
  }, [scheduleRefresh, clearTimer]);

  useEffect(() => {
    if (typeof window === 'undefined') return;

    const channel = new BroadcastChannel(AUTH_CHANNEL_NAME);
    channelRef.current = channel;

    channel.onmessage = (event: MessageEvent<AuthMessage>) => {
      const { type, payload } = event.data;

      switch (type) {
        case BROADCAST_EVENTS.LOGIN_SUCCESS: {
          const { user, expiresAt } = payload as LoginSuccessPayload;
          dispatch({ type: 'SET_AUTHENTICATED', payload: { user, expiresAt } });
          break;
        }
        case BROADCAST_EVENTS.REFRESH_SUCCESS: {
          const { expiresAt } = payload as RefreshSuccessPayload;
          dispatch({ type: 'SET_EXPIRES_AT', payload: expiresAt });
          break;
        }
        case BROADCAST_EVENTS.LOGOUT: {
          handleLogoutSync();
          break;
        }
        case BROADCAST_EVENTS.FORCE_REFRESH:
          performRefresh();
          break;
      }
    };

    return () => {
      channel.close();
      channelRef.current = null;
    };
  }, [handleLogoutSync, performRefresh]);

  const login = useCallback(
    async (credentials: LoginCredentials) => {
      dispatch({ type: 'SET_LOADING', payload: true });
      dispatch({ type: 'CLEAR_ERROR' });

      try {
        const data = await authService.login(credentials);
        dispatch({ type: 'SET_AUTHENTICATED', payload: { user: data.user, expiresAt: data.expires_at } });
        broadcast({ type: BROADCAST_EVENTS.LOGIN_SUCCESS, payload: { user: data.user, expiresAt: data.expires_at } });
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
    [broadcast]
  );

  const logout = useCallback(
    async (reason: LogoutReason = 'manual') => {
      await performLogout(reason);
    },
    [performLogout]
  );

  const refreshToken = useCallback(async () => {
    await performRefresh();
  }, [performRefresh]);

  const clearError = useCallback(() => {
    dispatch({ type: 'CLEAR_ERROR' });
  }, []);

  const hasPermission = useCallback(
    (permission: string): boolean => {
      if (!state.user || !state.isAuthenticated) return false;
      return state.user.permissions?.includes(permission) ?? false;
    },
    [state.user, state.isAuthenticated]
  );

  const hasRole = useCallback(
    (role: string): boolean => {
      if (!state.user || !state.isAuthenticated) return false;
      return state.user.role === role;
    },
    [state.user, state.isAuthenticated]
  );

  useEffect(() => {
    const controller = new AbortController();

    const initAuth = async () => {
      try {
        const data = await authService.getMe({ signal: controller.signal });
        const user: User = data.user || data;
        const expiresAt = data.expires_at;

        if (!expiresAt) {
          throw new Error('Invalid auth response: missing expires_at');
        }

        if (!controller.signal.aborted) {
          dispatch({ type: 'SET_AUTHENTICATED', payload: { user, expiresAt } });
        }
      } catch (error) {
        if (!controller.signal.aborted) {
          dispatch({ type: 'SET_LOADING', payload: false });
        }
      }
    };

    initAuth();

    return () => {
      controller.abort();
    };
  }, []);

  const contextValue: AuthContextValue = useMemo(
    () => ({
      user: state.user,
      isAuthenticated: state.isAuthenticated,
      isLoading: state.isLoading,
      error: state.error,
      login,
      logout,
      refreshToken,
      clearError,
      hasPermission,
      hasRole,
    }),
    [state.user, state.isAuthenticated, state.isLoading, state.error, login, logout, refreshToken, clearError, hasPermission, hasRole]
  );

  return <AuthContext.Provider value={contextValue}>{children}</AuthContext.Provider>;
}
