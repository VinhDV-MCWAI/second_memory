import { ReactNode } from 'react';
import type { SortOrder, ExportFormat, ImportFormat, ImageShape } from '../config/constant';

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
 * Import/Export Types (re-exported from constants for backward compatibility)
 */
export type { ExportFormat, ImportFormat, ImageShape };

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
 * Image Upload Component Props
 */
export interface ImageUploadProps {
  value?: string;
  onChange: (file: File | null, previewUrl: string | null) => void;
  maxSize?: number;
  className?: string;
  label?: string;
  shape?: ImageShape;
  aspectRatio?: string;
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
 * Department Tree Component Props
 */
export interface DepartmentTreeProps {
  departments: import('./models/master').DepartmentMst[];
  onSelect?: (department: import('./models/master').DepartmentMst) => void;
  selectedId?: number;
  className?: string;
}

export interface TreeNodeProps {
  department: import('./models/master').DepartmentMst;
  childNodes: import('./models/master').DepartmentMst[];
  level: number;
  onSelect?: (department: import('./models/master').DepartmentMst) => void;
  selectedId?: number;
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
 * Import/Export Component Props
 */
export interface ImportExportProps {
  onExport?: (format: ExportFormat) => Promise<void> | void;
  onImport?: (file: File, format: ImportFormat) => Promise<void> | void;
  onDownloadTemplate?: (format: ImportFormat) => Promise<void> | void;
  exportFormats?: ExportFormat[];
  importFormats?: ImportFormat[];
  moduleName?: string;
}

/**
 * Multi Select Component Props
 */
export interface MultiSelectProps {
  label?: string;
  placeholder?: string;
  options: SelectOption[];
  value: (string | number)[];
  onChange: (value: (string | number)[]) => void;
  required?: boolean;
  disabled?: boolean;
  className?: string;
}

/**
 * Permission Manager Types
 */
export interface Permission {
  id: number;
  name: string;
  description?: string;
  category?: string;
}

export interface PermissionGroup {
  name: string;
  permissions: Permission[];
}

export interface PermissionManagerProps {
  permissions: PermissionGroup[];
  selectedPermissions: number[];
  onChange: (selectedIds: number[]) => void;
  disabled?: boolean;
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
export interface DiffViewerProps {
  diffs: import('./models').HistoryDiff[];
  className?: string;
}

export interface TimelineProps {
  history: import('./models').BaseHistory[];
  className?: string;
}

export interface HistoryViewerProps {
  entityType?: string;
  entityId?: number;
  endpoint?: string;
  baseUrl?: string;
  recordId?: number;
  onRestore?: (historyId: number) => void;
  className?: string;
}
