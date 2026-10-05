/**
 * Generic CRUD Service
 * Provides reusable methods for Create, Read, Update, Delete operations
 */

import { apiClient } from '@/shared/api/client';
import type {
  PaginatedResponse,
  ListQueryParams,
  ImportResponse,
  CrudServiceConfig,
  RequiredEndpoints,
} from '@/shared/types/api';
import { API_PATHS } from '@/shared/types/api';
import { PAGINATION } from '@/shared/config/constant';

/**
 * Service Endpoints Configuration
 * @deprecated Use API_PATHS from '@/shared/types/api' instead
 */
export const SERVICE_ENDPOINTS = {
  LIST: API_PATHS.LIST,
  STORE: API_PATHS.STORE,
  UPDATE: API_PATHS.UPDATE,
  DELETE: API_PATHS.DELETE,
  GET: '',
} as const;

export class CrudService<T = Record<string, unknown>> {
  private baseUrl: string;
  private endpoints: RequiredEndpoints;

  constructor(config: CrudServiceConfig) {
    this.baseUrl = config.baseUrl;
    this.endpoints = {
      list: config.endpoints?.list ?? SERVICE_ENDPOINTS.LIST,
      get: config.endpoints?.get ?? SERVICE_ENDPOINTS.GET,
      create: config.endpoints?.create ?? SERVICE_ENDPOINTS.STORE,
      update: config.endpoints?.update ?? SERVICE_ENDPOINTS.UPDATE,
      delete: config.endpoints?.delete ?? SERVICE_ENDPOINTS.DELETE,
    };
  }

  /**
   * Get list of items with pagination
   */
  async list(params?: ListQueryParams): Promise<PaginatedResponse<T>> {
    const url = `${this.baseUrl}${this.endpoints.list}`;
    const response = await apiClient.get<PaginatedResponse<T>>(url, { params });
    return response.data;
  }

  /**
   * Get single item by ID
   */
  async getById(id: string | number): Promise<T | null> {
    const response = await this.list({ id, per_page: PAGINATION.DEFAULT_PER_PAGE } as ListQueryParams);
    return response.data[0] || null;
  }

  /**
   * Get single item by ID (alternative method using dedicated endpoint)
   */
  async get(id: string | number): Promise<T> {
    const url = `${this.baseUrl}${this.endpoints.get}/${id}`;
    const response = await apiClient.get<T>(url);
    return response.data;
  }

  /**
   * Create new item
   */
  async create(data: Partial<T>): Promise<number> {
    const url = `${this.baseUrl}${this.endpoints.create}`;
    const response = await apiClient.post<number>(url, data);
    return response.data;
  }

  /**
   * Update existing item
   */
  async update(id: string | number, data: Partial<T>): Promise<number> {
    const url = `${this.baseUrl}${this.endpoints.update}/${id}`;
    const response = await apiClient.put<number>(url, data);
    return response.data;
  }

  /**
   * Delete item by ID
   */
  async delete(id: string | number): Promise<void> {
    const url = `${this.baseUrl}${this.endpoints.delete}/${id}`;
    await apiClient.delete(url);
  }

  /**
   * Bulk delete items
   */
  async bulkDelete(ids: number[]): Promise<void> {
    const url = `${this.baseUrl}${this.endpoints.delete}`;
    await apiClient.post(url, { ids });
  }

  /**
   * Export data to CSV
   */
  async export(params?: ListQueryParams): Promise<Blob> {
    const url = `${this.baseUrl}${API_PATHS.EXPORT}`;
    const queryParams = new URLSearchParams(
      Object.entries(params || {}).reduce((acc, [key, value]) => {
        if (value !== undefined && value !== null) {
          acc[key] = String(value);
        }
        return acc;
      }, {} as Record<string, string>)
    );
    
    const response = await fetch(url + '?' + queryParams.toString(), {
      method: 'GET',
      headers: {
        'Accept': 'text/csv',
      },
    });
    return response.blob();
  }

  /**
   * Import data from file
   */
  async import(file: File): Promise<ImportResponse> {
    const url = `${this.baseUrl}${API_PATHS.IMPORT}`;
    const formData = new FormData();
    formData.append('file', file);

    const response = await apiClient.post<ImportResponse>(
      url,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    );
    return response.data;
  }
}

/**
 * Create a CRUD service instance for a specific module
 */
export function createCrudService<T = Record<string, unknown>>(config: CrudServiceConfig): CrudService<T> {
  return new CrudService<T>(config);
}
