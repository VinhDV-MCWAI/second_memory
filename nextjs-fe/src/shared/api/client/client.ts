import axios, {
  AxiosInstance,
  AxiosError,
  InternalAxiosRequestConfig,
  AxiosRequestConfig,
} from 'axios';
import { ApiResponse } from '@/shared/types/api';
import { API_ENDPOINTS, API_BASE_URL } from '@/shared/api/endpoints';

// Auth calls handle their own 401 (wrong password, no session yet)
const AUTH_PATHS: string[] = Object.values(API_ENDPOINTS.AUTH);

/** Laravel's status for a missing or stale CSRF token. */
const CSRF_MISMATCH = 419;

class ApiClient {
  private client: AxiosInstance;
  private unauthorizedHandler: (() => void) | null = null;
  private forbiddenHandler: (() => void) | null = null;

  constructor() {
    this.client = axios.create({
      baseURL: API_BASE_URL,
      timeout: 30000, // 30 seconds
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      withCredentials: true, // Session cookie (Sanctum SPA auth)
      withXSRFToken: true, // Send the XSRF-TOKEN cookie back as X-XSRF-TOKEN
    });

    this.setupInterceptors();
  }

  private setupInterceptors() {
    this.client.interceptors.request.use(
      (config: InternalAxiosRequestConfig) => {
        config.headers['Cache-Control'] = 'no-cache';
        config.headers['Pragma'] = 'no-cache';

        return config;
      },
      (error: AxiosError) => {
        return Promise.reject(error);
      },
    );

    this.client.interceptors.response.use(
      (response) => {
        // Check if response body has error.status = true (business logic error)
        const data = response.data;
        if (data && typeof data === 'object' && 'error' in data) {
          const errorInfo = data.error as { status: boolean; messages?: string | string[] | null };
          if (errorInfo.status === true && errorInfo.messages) {
            // Business logic error - show error message
            const errorMsg = Array.isArray(errorInfo.messages)
              ? errorInfo.messages.join(', ')
              : errorInfo.messages;
            console.error('[API Error]', errorMsg);
            // Don't reject, let the caller handle it
            // return Promise.reject(new Error(errorMsg));
          }
        }
        return response;
      },
      async (error: AxiosError) => {
        // A new session started under this page (a remembered device signed in again, AUTH-05), so the
        // page's CSRF token is stale: fetch the new XSRF cookie and send the request once more
        const retry = error.config as
          | (InternalAxiosRequestConfig & { csrfRetried?: boolean })
          | undefined;
        if (error.response?.status === CSRF_MISMATCH && retry && !retry.csrfRetried) {
          retry.csrfRetried = true;
          await this.client.get(API_ENDPOINTS.AUTH.CSRF_COOKIE);
          return this.client.request(retry);
        }

        // The session ended (logout elsewhere, expired, admin disabled): let the auth provider sign out
        const url = error.config?.url ?? '';
        const isAuthPath = AUTH_PATHS.some((path) => url.includes(path));
        if (error.response?.status === 401 && !isAuthPath) {
          this.unauthorizedHandler?.();
        }
        // Permissions may have changed since they were loaded: let the auth provider reload them
        if (error.response?.status === 403 && !isAuthPath) {
          this.forbiddenHandler?.();
        }

        // Error toasts are shown by the caller (see getApiErrorMessage), which knows the context.
        return Promise.reject(error);
      },
    );
  }

  /** Called on a 401 from any non-auth endpoint. */
  onUnauthorized(handler: (() => void) | null): void {
    this.unauthorizedHandler = handler;
  }

  /** Called on a 403 from any non-auth endpoint. */
  onForbidden(handler: (() => void) | null): void {
    this.forbiddenHandler = handler;
  }

  async get<T>(url: string, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
    const response = await this.client.get<ApiResponse<T>>(url, config);
    return response.data;
  }

  async post<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
    const response = await this.client.post<ApiResponse<T>>(url, data, config);
    return response.data;
  }

  async put<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
    const response = await this.client.put<ApiResponse<T>>(url, data, config);
    return response.data;
  }

  async delete<T>(url: string, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
    const response = await this.client.delete<ApiResponse<T>>(url, config);
    return response.data;
  }

  getAxiosInstance(): AxiosInstance {
    return this.client;
  }
}

export const apiClient = new ApiClient();

export { ApiClient };

export default apiClient.getAxiosInstance();
