/**
 * User type moved to api.ts for centralization
 * @deprecated Import from '@/shared/types/api' instead
 */
import type { User } from './api';
export type { User } from './api';

export interface LoginCredentials {
  user_name: string;
  password: string;
  remember?: boolean;
}

export interface RegisterCredentials {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface AuthResponse {
  user: User;
  expires_at: number;
  token?: string;
  refresh_token?: string;
}

export interface RefreshTokenResponse {
  expires_at: number;
  token?: string;
}

export interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  expiresAt: number | null;
  error: AuthError | null;
}

export interface AuthError {
  message: string;
  code?: string;
  status?: number;
  field?: string;
}

export const AUTH_CHANNEL_NAME = 'auth_sync_channel';

/**
 * Broadcast message types - should match AuthBroadcastEvents in auth constants
 */
export type AuthMessageType =
  | 'LOGIN_SUCCESS'
  | 'REFRESH_SUCCESS'
  | 'LOGOUT'
  | 'FORCE_REFRESH'
  | 'AUTH_ERROR';

export interface AuthMessage {
  type: AuthMessageType;
  payload?: AuthMessagePayload;
}

export type AuthMessagePayload =
  | LoginSuccessPayload
  | RefreshSuccessPayload
  | LogoutPayload
  | AuthErrorPayload;

export interface LoginSuccessPayload {
  user: User;
  expiresAt: number;
}

export interface RefreshSuccessPayload {
  expiresAt: number;
}

export interface LogoutPayload {
  reason?: LogoutReason;
}

/**
 * Logout reason types - should match LogoutReasons in auth constants
 */
export type LogoutReason = 'manual' | 'session_expired' | 'token_refresh_failed' | 'unknown';

export interface AuthErrorPayload {
  error: AuthError;
}

export interface AuthContextValue {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: AuthError | null;
  login: (credentials: LoginCredentials) => Promise<void>;
  logout: (reason?: LogoutReason) => Promise<void>;
  refreshToken: () => Promise<void>;
  clearError: () => void;
  hasPermission: (permission: string) => boolean;
  hasRole: (role: string) => boolean;
}

export interface RefreshTokenConfig {
  refreshBeforeExpiry?: number;
  maxRetries?: number;
  retryDelay?: number;
}

export const DEFAULT_REFRESH_CONFIG: RefreshTokenConfig = {
  refreshBeforeExpiry: 30000,
  maxRetries: 3,
  retryDelay: 1000,
};

export type AuthAction =
  | { type: 'SET_LOADING'; payload: boolean }
  | { type: 'SET_USER'; payload: User }
  | { type: 'SET_EXPIRES_AT'; payload: number }
  | { type: 'SET_AUTHENTICATED'; payload: { user: User; expiresAt: number } }
  | { type: 'SET_ERROR'; payload: AuthError }
  | { type: 'CLEAR_ERROR' }
  | { type: 'LOGOUT' }
  | { type: 'RESET' };

// From authType.ts
export type LoginPayload = {
  user_name: string;
  password: string;
}

export type StoreAuthState = {
  accessToken: string | null;
  isAuthenticated: boolean;
  redirectUrl?: string | null;
  tabId: string | null; // Current tab ID
  leaderId: string | null; // ID of leader tab managing refresh
  refreshAtTime: number | null; // Time when token should be refreshed (in ms)
  authInitialized: boolean; // Whether auth initialization has completed
}

export type LoginResponseData = {
  auth_type: string;
  ttl: number;
  access_token: string;
}
