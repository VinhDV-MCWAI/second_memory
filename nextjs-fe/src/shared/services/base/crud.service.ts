import { apiClient } from '@/shared/api/client';
import type { ApiResponse, PaginatedResponse, ListQueryParams } from '@/shared/types';

export class BaseCrudService<T> {
  constructor(protected endpoint: string) {}

  async getAll(params?: ListQueryParams): Promise<PaginatedResponse<T>> {
    const response = await apiClient.get<PaginatedResponse<T>>(this.endpoint, { params });
    return response.data;
  }

  async getById(id: number): Promise<ApiResponse<T>> {
    const response = await apiClient.get<ApiResponse<T>>(`${this.endpoint}/${id}`);
    return response.data;
  }

  async create(data: Partial<T>): Promise<ApiResponse<T>> {
    const response = await apiClient.post<ApiResponse<T>>(this.endpoint, data);
    return response.data;
  }

  async update(id: number, data: Partial<T>): Promise<ApiResponse<T>> {
    const response = await apiClient.put<ApiResponse<T>>(`${this.endpoint}/${id}`, data);
    return response.data;
  }

  async delete(id: number): Promise<ApiResponse<void>> {
    const response = await apiClient.delete<ApiResponse<void>>(`${this.endpoint}/${id}`);
    return response.data;
  }

  async bulkDelete(ids: number[]): Promise<ApiResponse<void>> {
    const response = await apiClient.post<ApiResponse<void>>(`${this.endpoint}/bulk-delete`, { ids });
    return response.data;
  }
}
