import type { AxiosRequestConfig } from "axios";
import { authLock } from "@/shared/utils/auth-lock";
import { apiClient } from "@/shared/api/client";
import { ENDPOINTS } from "@/shared/api";
import type {
  LoginCredentials,
  AuthResponse,
  RefreshTokenResponse,
} from "@/shared/types/auth.types";
import type {
  LoginApiResponse,
  RefreshApiResponse,
  MeApiResponse,
} from "@/shared/types/api";

export const authService = {
  async login(credentials: LoginCredentials): Promise<AuthResponse> {
    const response = await apiClient.post<LoginApiResponse>(
      ENDPOINTS.AUTH.LOGIN,
      credentials
    );
    return response.data;
  },

  async logout(): Promise<void> {
    await apiClient.post(ENDPOINTS.AUTH.LOGOUT);
  },

  async refreshToken(): Promise<RefreshTokenResponse> {
    const response = await apiClient.post<RefreshApiResponse>(
      ENDPOINTS.AUTH.REFRESH
    );
    return response.data;
  },

  async getMe(config?: AxiosRequestConfig): Promise<AuthResponse> {
    const response = await apiClient.get<MeApiResponse>(ENDPOINTS.AUTH.ME, config);
    return response.data;
  },

  async acquireRefreshLock(): Promise<boolean> {
    return await authLock.acquire();
  },

  releaseRefreshLock(): void {
    authLock.release();
  },

  isRefreshLocked(): boolean {
    return authLock.isLocked();
  },
};
