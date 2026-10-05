import axios, {
  AxiosInstance,
  AxiosError,
  InternalAxiosRequestConfig,
  AxiosRequestConfig,
} from "axios";
import { handleCommonError } from "./error-handler";
import { ApiResponse } from "@/shared/types/api";
import { authLock } from "@/shared/utils/auth-lock";
import { API_ENDPOINTS, API_BASE_URL } from "@/shared/api/endpoints";

class ApiClient {
  private client: AxiosInstance;

  constructor() {
    this.client = axios.create({
      baseURL: API_BASE_URL,
      timeout: 30000, // 30 seconds
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      withCredentials: true, // Always send cookies
    });

    this.setupInterceptors();
  }

  private setupInterceptors() {
    this.client.interceptors.request.use(
      (config: InternalAxiosRequestConfig) => {
        config.headers["Cache-Control"] = "no-cache";
        config.headers["Pragma"] = "no-cache";

        return config;
      },
      (error: AxiosError) => {
        return Promise.reject(error);
      }
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
        const originalRequest = error.config as InternalAxiosRequestConfig & {
          _retry?: boolean;
        };

        if (
          error.response?.status === 401 &&
          originalRequest &&
          !originalRequest._retry
        ) {
          if (
            originalRequest.url?.includes(API_ENDPOINTS.AUTH.REFRESH) ||
            originalRequest.url?.includes(API_ENDPOINTS.AUTH.LOGIN)
          ) {
            return Promise.reject(error);
          }

          originalRequest._retry = true;

          try {
            if (authLock.isLocked()) {
              await this.waitForLock();
              return this.client(originalRequest);
            }

            if (await authLock.acquire()) {
              try {
                await axios.post(
                  `${this.client.defaults.baseURL}${API_ENDPOINTS.AUTH.REFRESH}`,
                  {},
                  { withCredentials: true }
                );

                return this.client(originalRequest);
              } catch (refreshError) {
                return Promise.reject(refreshError);
              } finally {
                authLock.release();
              }
            } else {
              await this.waitForLock();
              return this.client(originalRequest);
            }
          } catch (err) {
            return Promise.reject(err);
          }
        }

        return handleCommonError(error as AxiosError<ApiResponse<unknown> | { message?: string }>);
      }
    );
  }

  private async waitForLock(): Promise<void> {
    const checkInterval = 100;
    const maxWait = 5000;
    let waited = 0;

    while (authLock.isLocked() && waited < maxWait) {
      await new Promise((resolve) => setTimeout(resolve, checkInterval));
      waited += checkInterval;
    }
  }

  async get<T>(
    url: string,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    const response = await this.client.get<ApiResponse<T>>(url, config);
    return response.data;
  }

  async post<T>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    const response = await this.client.post<ApiResponse<T>>(url, data, config);
    return response.data;
  }

  async put<T>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    const response = await this.client.put<ApiResponse<T>>(url, data, config);
    return response.data;
  }

  async delete<T>(
    url: string,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
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
