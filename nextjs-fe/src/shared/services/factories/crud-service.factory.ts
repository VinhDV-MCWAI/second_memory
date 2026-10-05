/**
 * CRUD Service Factory
 * Creates standardized CRUD service instances for different entity types
 */

import { apiClient } from '@/shared/api/client';
import type {
  PaginatedResponse,
  ListQueryParams,
  ServiceConfig,
  CrudServiceOperations,
} from '@/shared/types/api';
import { API_PATHS } from '@/shared/types/api';

/**
 * Creates a CRUD service with standard operations
 * 
 * @example
 * // Without suffix (direct endpoint)
 * const userService = createCrudService<UserMgmt>({
 *   endpoint: ENDPOINTS.MANAGEMENT.USER
 * });
 * 
 * @example
 * // With suffix pattern
 * const tokenService = createCrudService<TokenMst>({
 *   endpoint: ENDPOINTS.MASTER.TOKEN,
 *   useSuffix: true
 * });
 */
export function createCrudService<T>(config: ServiceConfig): CrudServiceOperations<T> {
  const { endpoint, useSuffix = false } = config;

  // Build endpoint URLs based on pattern
  const endpoints = {
    list: useSuffix ? `${endpoint}${API_PATHS.LIST}` : endpoint,
    store: useSuffix ? `${endpoint}${API_PATHS.STORE}` : endpoint,
    update: (id: number) => useSuffix ? `${endpoint}${API_PATHS.UPDATE}/${id}` : `${endpoint}/${id}`,
    delete: useSuffix ? `${endpoint}${API_PATHS.DELETE}` : endpoint,
  };

  return {
    async list(params: ListQueryParams = {}) {
      return apiClient.get<PaginatedResponse<T>>(endpoints.list, { params });
    },

    async getById(id: number) {
      const response = await this.list({ id, per_page: 1 });
      return response.data.data[0] || null;
    },

    async create(data: Omit<T, 'id' | 'updated_at' | 'created_at'>) {
      return apiClient.post<number>(endpoints.store, data);
    },

    async update(id: number, data: Partial<T>) {
      return apiClient.put<number>(endpoints.update(id), { id, ...data });
    },

    async delete(ids: number[]) {
      await apiClient.delete(endpoints.delete, { data: { ids } });
    },
  };
}
