/**
 * API Response Types
 */

import { SORT_ORDER } from '../config/constant';

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
  sort_order?: typeof SORT_ORDER[keyof typeof SORT_ORDER];
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
  sort_order?: typeof SORT_ORDER[keyof typeof SORT_ORDER];
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

export interface UseCrudReturn<T> {
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
 * useHistory Hook Types
 */

import type { BaseHistory, HistoryDiff } from './models/history';

export interface UseHistoryOptions {
  baseUrl: string;
  recordId: number;
}

export interface UseHistoryReturn<T extends BaseHistory> {
  history: T[];
  isLoading: boolean;
  error: Error | null;
  pagination: {
    page: number;
    perPage: number;
    total: number;
  };
  fetchHistory: (filters?: Record<string, unknown>) => Promise<void>;
  compareVersions: (oldVersion: T, newVersion: T) => HistoryDiff[];
  restoreVersion: (historyId: number) => Promise<void>;
}

/**
 * useJunctionTable Hook Types
 */

export interface UseJunctionTableReturn<T = Record<string, unknown>> {
  allItems: T[];
  assignedIds: number[];
  selectedIds: number[];
  loading: boolean;
  saving: boolean;
  setSelectedIds: (ids: number[]) => void;
  toggleSelection: (id: number) => void;
  save: () => Promise<void>;
  refetch: () => Promise<void>;
}

/**
 * Master Data Models
 */

export interface AdminMst {
  id: number;
  email: string;
  user_name: string;
  password?: string;
  first_name: string;
  last_name: string;
  address?: string;
  phone_number?: string;
  birth?: string | null;
  gender: number;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  avatar?: string;
  updated_at: string;
  created_at?: string;
  // Relationships (when included)
  roles?: RoleMst[];
  departments?: DepartmentMst[];
}

export interface RoleMst {
  id: number;
  name: string;
  permission: string;
  is_active: boolean;
  is_delete: boolean;
  updated_at: string;
  // Relationships
  apis?: ApiMst[];
  features?: FeatureMst[];
}

export interface DepartmentMst {
  id: number;
  code: string;
  name: string;
  status: number;
  is_delete: boolean;
  updated_at: string;
}

export interface FeatureMst {
  id: number;
  name: string;
  group_name?: string;
  status: number;
  is_delete: boolean;
  updated_at: string;
}

export interface ApiMst {
  id: number;
  type: number;
  name: string;
  path: string;
  is_active: boolean;
  feature_mst_id: number;
  is_delete: boolean;
  updated_at: string;
  // Relationships
  feature?: FeatureMst;
}

export interface TokenMst {
  id: number;
  account_id: number;
  device_name: string;
  ip_address: string;
  expired_at: string;
  updated_at: string;
}

export interface PolicyDepartmentMst {
  id: number;
  table_name: string;
  row_id: number;
  is_delete: boolean;
  updated_at: string;
  // Relationships
  departments?: DepartmentMst[];
}



/**
 * Management Data Models
 */

export interface BannerMgmt {
  id: number;
  title: string;
  slug?: string;
  description?: string;
  image?: string;
  position?: string;
  status: number;
  is_delete: boolean;
  updated_at: string;
}

export interface LayoutStructureItem {
  ui_id: string;
  entry_desc_id?: number;
  entry_mgmt_id?: number;
  name?: string;
  slug?: string;
  children?: LayoutStructureItem[];
}

export interface CategoryMgmt {
  id: number;
  name: string;
  slug: string;
  description?: string;
  status: number;
  is_display: boolean;
  rank_order: number;
  is_delete: boolean;
  layout_structure?: LayoutStructureItem[];
  updated_at: string;
  // Relationships
  entries?: EntryMgmt[];
}

export interface EntryMgmt {
  id: number;
  name: string;
  slug?: string;
  status: number;
  is_display?: boolean;
  rank_order: number;
  is_delete: boolean;
  layout_structure?: LayoutStructureItem[];
  updated_at: string;
  // Relationships
  entry_descriptions?: EntryDescriptionMgmt[];
  categories?: CategoryMgmt[];
}

export interface EntryDescriptionMgmt {
  id: number;
  title: string;
  summary?: string;
  article?: string;
  status: number;
  is_display: boolean;
  rank_order: number;
  entry_mgmt_id: number;
  is_delete: boolean;
  updated_at: string;
}

export interface SliderMgmt {
  id: number;
  title: string;
  slug?: string;
  link?: string;
  image?: string;
  status: number;
  is_delete: boolean;
  updated_at: string;
}

export interface SocialMgmt {
  id: number;
  name: string;
  slug?: string;
  link: string;
  image?: string;
  status: number;
  is_display: boolean;
  rank_order: number;
  is_delete: boolean;
  updated_at: string;
}

export interface UserMgmt {
  id: number;
  email: string;
  user_name: string;
  password?: string;
  first_name: string;
  last_name: string;
  address?: string;
  phone_number?: string;
  birth?: string | null;
  gender: number;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  avatar?: string;
  updated_at: string;
}

export interface SettingLinkMgmt {
  id: number;
  name: string;
  url: string;
  description?: string;
  rank_order: number;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  updated_at: string;
}

/**
 * Junction Table Models
 */

export interface AdminRoleMst {
  admin_mst_id: number;
  role_mst_id: number;
}

export interface AdminDepartmentMst {
  admin_mst_id: number;
  department_mst_id: number;
}

export interface ApiRoleMst {
  api_mst_id: number;
  role_mst_id: number;
}

export interface DepartmentManagementMst {
  department_mst_id: number;
  policy_department_mst_id: number;
}



export interface CategoryEntryMgmt {
  category_mgmt_id: number;
  entry_mgmt_id: number;
}

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

export interface AuthUser {
  id: number;
  email: string;
  user_name: string;
  first_name: string;
  last_name: string;
  avatar?: string;
  roles?: RoleMst[];
  departments?: DepartmentMst[];
}



/**
 * API Endpoint Paths Constants
 */
export const API_PATHS = {
  LIST: '/list',
  STORE: '/store',
  UPDATE: '/update',
  DELETE: '/delete',
  VIEW: '/view',
  DOWNLOAD: '/download',
  EXPORT: '/export',
  IMPORT: '/import',
  PREPARE_UPLOAD: '/prepare-upload',
  INIT_MULTIPART_UPLOAD: '/init-multipart-upload',
  GET_MULTIPART_URL: '/get-multipart-url',
  COMPLETE_MULTIPART_UPLOAD: '/complete-multipart-upload',
} as const;

/**
 * User Type
 */
export interface User {
  id: string | number;
  email: string;
  name: string;
  avatar?: string;
  role?: string;
  permissions?: string[];
  created_at?: string;
  updated_at?: string;
}

/**
 * Auth Service Response Types
 */
export interface LoginApiResponse {
  user: User;
  expires_at: number;
  token?: string;
}

export interface RefreshApiResponse {
  expires_at: number;
  token?: string;
}

export interface MeApiResponse {
  user: User;
  expires_at: number;
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
