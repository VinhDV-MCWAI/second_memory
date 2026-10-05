/**
 * UI Components Types
 */

import { Button } from '@/components/ui/button';

export interface ConfirmDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description: string;
  onConfirm: () => Promise<void> | void;
  onCancel?: () => void;
  confirmText?: string;
  cancelText?: string;
  variant?: 'default' | 'destructive';
  isLoading?: boolean;
}

type ButtonProps = React.ComponentProps<typeof Button>;

export interface SafeButtonProps extends Omit<ButtonProps, 'onClick'> {
  /**
   * The async action to execute when clicked.
   */
  onSafeClick?: () => Promise<unknown>;
  /**
   * Optional standard onClick handler (will be ignored if onSafeClick is provided).
   */
  onClick?: React.MouseEventHandler<HTMLButtonElement>;
  /**
   * Whether to show a loading spinner when the action is executing.
   * Default: true
   */
  showLoading?: boolean;
}

export type LoadingSpinnerSize = 'sm' | 'md' | 'lg';

export interface LoadingSpinnerProps {
  size?: LoadingSpinnerSize;
}

export interface LoadingOverlayProps {
  message?: string;
}

export interface ScreenBlockerProps {
  isVisible: boolean;
  message?: string;
}

export interface LoadingSkeletonProps {
  rows?: number;
}

export type TextareaProps = React.TextareaHTMLAttributes<HTMLTextAreaElement>;

/**
 * Navigation Types
 */
import { LucideIcon } from 'lucide-react';

export interface MenuItem {
  label: string;
  icon: LucideIcon;
  href?: string;
  children?: MenuItem[];
  badge?: string | number;
  disabled?: boolean;
}

/**
 * Layout Component Types
 */
import { ReactNode } from 'react';

/**
 * Breadcrumb Item Type
 */
export interface BreadcrumbItemType {
  label: string;
  href?: string;
  isActive?: boolean;
}

/**
 * Admin Layout Props
 */
export interface AdminLayoutProps {
  children: ReactNode;
  className?: string;
}

/**
 * Admin Nav Dropdown Props
 */
export interface AdminNavDropdownProps {
  currentLabel?: string;
  variant?: 'default' | 'outline' | 'ghost';
  size?: 'default' | 'sm' | 'lg';
  className?: string;
}

/**
 * Content Props
 */
export interface ContentProps {
  children: ReactNode;
  className?: string;
  padded?: boolean;
  fullWidth?: boolean;
}

/**
 * Page Header Props
 */
export interface PageHeaderProps {
  title: string;
  description?: string;
  breadcrumbs?: BreadcrumbItemType[];
  action?: ReactNode;
  showBackButton?: boolean;
  onBackClick?: () => void;
  className?: string;
}

/**
 * Search Filter Props
 */
export interface SearchFilterProps {
  placeholder?: string;
  onSearch?: (value: string) => void;
  onFilterClick?: () => void;
  showFilter?: boolean;
  debounceMs?: number;
  className?: string;
}

/**
 * Notification Type for Header
 */
export interface Notification {
  id: string;
  title: string;
  message: string;
  read: boolean;
  timestamp: Date;
}

/**
 * Lazy Image Props
 */
export interface LazyImageProps extends React.ImgHTMLAttributes<HTMLImageElement> {
  src: string;
  alt: string;
  className?: string;
}

/**
 * Image Picker Props
 */
export interface ImagePickerProps {
  value?: string | null;
  onChange: (url: string, id?: number) => void;
  error?: string;
  label?: string;
  required?: boolean;
}
