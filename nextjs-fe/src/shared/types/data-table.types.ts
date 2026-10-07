import { ReactNode } from 'react';
import type { SortOrder } from '@/shared/config';

/**
 * Data Table Column Definition
 */
export interface Column<T> {
  key: string;
  label: string;
  sortable?: boolean;
  render?: (item: T) => ReactNode;
}

/**
 * Data Table Props
 */
export interface DataTableProps<T> {
  data: T[];
  columns: Column<T>[];
  loading?: boolean;
  selectedIds?: number[];
  onSelectionChange?: (ids: number[]) => void;
  onSort?: (column: string) => void;
  sortBy?: string;
  sortOrder?: SortOrder;
  onEdit?: (id: number) => void;
  onDelete?: (id: number) => void;
  showActions?: boolean;
  idKey?: keyof T;
}

/**
 * Pagination Information
 */
export interface PaginationInfo {
  currentPage: number;
  lastPage: number;
  total: number;
  perPage: number;
  from: number;
  to: number;
}

/**
 * Pagination Component Props
 */
export interface PaginationProps {
  pagination: PaginationInfo;
  page: number;
  onPageChange: (page: number) => void;
  perPage: number;
  onPerPageChange: (perPage: number) => void;
}

/**
 * Filter Field Definition
 */
export interface FilterField {
  key: string;
  label: string;
  type: 'text' | 'select' | 'date' | 'boolean';
  options?: { value: string | number; label: string }[];
  placeholder?: string;
}

/**
 * Filter Panel Props
 */
export interface FilterPanelProps {
  filters: Record<string, unknown>;
  onFilterChange: (filters: Record<string, unknown>) => void;
  onReset: () => void;
  fields?: FilterField[];
}

/**
 * Common Select Option
 */
export interface SelectOption {
  value: string | number;
  label: string;
  disabled?: boolean;
}

/**
 * Search Field for Advanced Search
 */
export interface SearchField {
  key: string;
  label: string;
  type: 'text' | 'number' | 'date' | 'select';
  options?: SelectOption[];
}

/**
 * Search Criteria
 */
export interface SearchCriteria {
  field: string;
  operator: string;
  value: string;
}

/**
 * Advanced Search Props
 */
export interface AdvancedSearchProps {
  fields: SearchField[];
  onSearch: (criteria: SearchCriteria[]) => void;
  className?: string;
}

/**
 * Avatar Upload Component Props
 */
export interface AvatarUploadProps {
  value?: string;
  onChange: (file: File | null, previewUrl: string | null) => void;
  maxSize?: number;
  className?: string;
}

/**
 * Bulk Actions
 */
export interface BulkAction {
  label: string;
  icon?: React.ReactNode;
  variant?: 'default' | 'destructive' | 'outline';
  onClick: (selectedIds: number[]) => void | Promise<void>;
  confirmMessage?: string;
  confirmTitle?: string;
}

export interface BulkActionsProps {
  selectedIds: number[];
  onClearSelection: () => void;
  actions?: BulkAction[];
  isLoading?: boolean;
}

/**
 * Field Renderer Types
 */
export type FieldType =
  | 'text'
  | 'email'
  | 'password'
  | 'number'
  | 'textarea'
  | 'select'
  | 'checkbox'
  | 'date'
  | 'datetime'
  | 'file'
  | 'image';

export interface FieldConfig {
  name: string;
  label: string;
  type: FieldType;
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  options?: SelectOption[];
  accept?: string;
  min?: number;
  max?: number;
  rows?: number;
  className?: string;
  description?: string;
}

export interface FieldRendererProps {
  field: FieldConfig;
  value: unknown;
  onChange: (value: unknown) => void;
  error?: string;
}

/**
 * Form Builder Types
 */
export type FormLayout = 'single' | 'two-column' | 'tabs';

export interface FormSection {
  title: string;
  description?: string;
  fields: FieldConfig[];
}

export interface FormSchema {
  title?: string;
  description?: string;
  layout?: FormLayout;
  sections?: FormSection[];
  fields?: FieldConfig[];
}

export interface FormBuilderProps {
  schema: FormSchema;
  initialValues?: Record<string, unknown>;
  onSubmit: (values: Record<string, unknown>) => void | Promise<void>;
  onCancel?: () => void;
  submitLabel?: string;
  cancelLabel?: string;
  isLoading?: boolean;
  className?: string;
}

/**
 * Saved Filters Types
 */
export interface SavedFilter {
  id: string;
  name: string;
  filters: Record<string, unknown>;
  isDefault?: boolean;
}

export interface SavedFiltersProps {
  currentFilters: Record<string, unknown>;
  onApplyFilter: (filters: Record<string, unknown>) => void;
  storageKey?: string;
  className?: string;
}

/**
 * History Components Types
 */
export interface HistoryViewerProps {
  /** audit_log.auditable_type of the record, e.g. `admin`. */
  auditableType: string;
  recordId: number;
  className?: string;
}
