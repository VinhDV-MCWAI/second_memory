/**
 * API Response Types
 */

import { SORT_ORDER } from '@/shared/config';

export interface ApiResponse<T> {
  data: T;
  error: {
    status: boolean;
    code: number;
    messages: string | string[] | null;
  };
}

export interface ApiErrorResponse {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
  status_code: number;
}

export interface PaginatedResponse<T> {
  current_page: number;
  data: T[];
  first_page_url: string;
  from: number;
  last_page: number;
  last_page_url: string;
  links: PaginationLink[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number;
  total: number;
}

export interface PaginationSource {
  meta?: {
    current_page?: number;
    last_page?: number;
    total?: number;
    per_page?: number;
    from?: number;
    to?: number;
  };
  current_page?: number;
  currentPage?: number;
  last_page?: number;
  lastPage?: number;
  total?: number;
  per_page?: number;
  perPage?: number;
  from?: number;
  to?: number;
}

export interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

/**
 * Common Query Parameters
 */

export type FilterValue = string | number | boolean | null | undefined;

export interface ListQueryParams {
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_order?: (typeof SORT_ORDER)[keyof typeof SORT_ORDER];
  from_date?: string; // Format: d/m/Y
  to_date?: string; // Format: d/m/Y
  [key: string]: FilterValue;
}

/**
 * useApiData Hook Types
 */

export interface UseApiDataOptions {
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_order?: (typeof SORT_ORDER)[keyof typeof SORT_ORDER];
  from_date?: string;
  to_date?: string;
  filters?: Record<string, FilterValue>;
  enabled?: boolean; // If false, don't fetch automatically
  staleTime?: number;
  refetchOnMount?: boolean | 'always';
}

export interface UseApiDataReturn<T> {
  data: T[];
  loading: boolean;
  error: Error | null;
  pagination: {
    currentPage: number;
    lastPage: number;
    total: number;
    perPage: number;
    from: number;
    to: number;
  };
  refetch: () => void;
  isRefetching: boolean;
}

/**
 * useCrud Hook Types
 */

export interface UseCrudReturn {
  create: (data: Record<string, unknown>) => Promise<number>;
  update: (id: number, data: Record<string, unknown>) => Promise<number>;
  remove: (ids: number[]) => Promise<void>;
  loading: boolean;
  error: Error | null;
}

export interface UseCrudOptions {
  /**
   * Query keys to invalidate after successful mutation
   * Example: ['users'] will invalidate all user-related queries
   */
  invalidateKeys?: string[];

  /**
   * Custom success messages
   */
  messages?: {
    create?: string;
    update?: string;
    delete?: string;
  };
}

/**
 * API models (generated from the OpenAPI spec, see ./models)
 */

export type * from './models';

/**
 * Junction Table Update Requests
 */

export interface UpdateJunctionRequest<T> {
  delete?: T[];
  insert?: T[];
}

/**
 * Authentication Types
 */

export interface LoginRequest {
  user_name: string;
  password: string;
}

export interface LoginResponse {
  auth_type: string;
  ttl: number;
  access_token: string;
  _cookie?: string;
}

/**
 * Service Query Parameters
 * Extended query params for service-specific filtering
 */

export interface BaseServiceListParams extends ListQueryParams {
  id?: number;
  name?: string;
  status?: number;
  is_active?: boolean;
  is_delete?: boolean;
}

export type ApiListParams = BaseServiceListParams;
export type FeatureListParams = BaseServiceListParams;
export type RoleListParams = BaseServiceListParams;

export interface TokenListParams extends BaseServiceListParams {
  token?: string;
  admin_mst_id?: number;
}

/**
 * Import/Export Response Types
 */

export interface ImportResponse {
  success: number;
  failed: number;
  errors?: Array<{
    row: number;
    message: string;
    data?: unknown;
  }>;
}

/**
 * Service Factory Types
 */

/**
 * Service configuration for endpoints
 */
export interface ServiceConfig {
  /** Base endpoint URL */
  endpoint: string;
  /** Use suffix pattern (/list, /store, /update, /delete) instead of direct endpoint */
  useSuffix?: boolean;
}

/**
 * Standard CRUD operations interface
 */
export interface CrudServiceOperations<T> {
  list(params?: ListQueryParams): Promise<{ data: PaginatedResponse<T> }>;
  getById(id: number): Promise<T | null>;
  create(data: Omit<T, 'id' | 'updated_at' | 'created_at'>): Promise<{ data: number }>;
  update(id: number, data: Partial<T>): Promise<{ data: number }>;
  delete(ids: number[]): Promise<void>;
}

/**
 * CRUD Service Class Configuration
 */
export interface CrudServiceConfig {
  baseUrl: string;
  endpoints?: {
    list?: string;
    get?: string;
    create?: string;
    update?: string;
    delete?: string;
  };
}

export interface RequiredEndpoints {
  list: string;
  get: string;
  create: string;
  update: string;
  delete: string;
}
