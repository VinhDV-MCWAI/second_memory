import type { AxiosRequestConfig } from 'axios';
import { apiClient } from '@/shared/api/client';
import { ENDPOINTS } from '@/shared/api';
import type { LoginCredentials, User } from '@/shared/types/auth.types';

export const authService = {
  /** Sanctum SPA login: fetch the CSRF cookie first, then post the credentials. */
  async login(credentials: LoginCredentials): Promise<User> {
    await apiClient.get(ENDPOINTS.AUTH.CSRF_COOKIE);
    const response = await apiClient.post<User>(ENDPOINTS.AUTH.LOGIN, credentials);
    return response.data;
  },

  async logout(): Promise<void> {
    await apiClient.post(ENDPOINTS.AUTH.LOGOUT);
  },

  async getMe(config?: AxiosRequestConfig): Promise<User> {
    const response = await apiClient.get<User>(ENDPOINTS.AUTH.ME, config);
    return response.data;
  },
};
