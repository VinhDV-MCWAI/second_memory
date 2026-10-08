import type { AdminMst } from './models/master';

/** The signed-in admin, as `POST /admin/credential/login` and `GET /admin/credential/me` return it. */
export type User = AdminMst;

export interface LoginCredentials {
  user_name: string;
  password: string;
}

export interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: AuthError | null;
}

export interface AuthError {
  message: string;
  code?: string;
  status?: number;
}

/** BroadcastChannel name used to sync login / logout between tabs. */
export const AUTH_CHANNEL_NAME = 'auth_sync_channel';

export type LogoutReason = 'manual' | 'session_expired';

export type AuthMessage =
  | { type: 'LOGIN_SUCCESS'; payload: { user: User } }
  | { type: 'LOGOUT'; payload: { reason: LogoutReason } };

export interface AuthContextValue {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: AuthError | null;
  login: (credentials: LoginCredentials) => Promise<void>;
  logout: (reason?: LogoutReason) => Promise<void>;
  clearError: () => void;
}

export type AuthAction =
  | { type: 'SET_LOADING'; payload: boolean }
  | { type: 'SET_AUTHENTICATED'; payload: User }
  | { type: 'SET_ERROR'; payload: AuthError }
  | { type: 'CLEAR_ERROR' }
  | { type: 'LOGOUT' };
