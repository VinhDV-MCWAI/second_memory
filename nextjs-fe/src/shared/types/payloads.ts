/**
 * API Payload Types
 * These types represent the data structure sent to the backend API
 * Different from form data types which may have different field types (e.g., boolean vs number)
 */

import type { LayoutStructureItem } from './api';

// ============================================
// CATEGORY MANAGEMENT PAYLOADS
// ============================================
export interface CategoryCreatePayload {
  name: string;
  slug: string;
  description?: string;
  rank_order: number;
  status: number; // CategoryStatus enum value
  is_display: number; // IsActive enum value (0 or 1)
  is_delete: boolean;
  layout_structure?: LayoutStructureItem[];
}

export type CategoryUpdatePayload = CategoryCreatePayload;

// ============================================
// ENTRY MANAGEMENT PAYLOADS
// ============================================
export interface EntryCreatePayload {
  name: string;
  slug: string;
  description?: string;
  rank_order: number;
  status: number; // EntryStatus enum value
  is_display: number; // IsActive enum value (0 or 1)
  is_delete: boolean;
  layout_structure?: LayoutStructureItem[];
}

export type EntryUpdatePayload = EntryCreatePayload;

// ============================================
// ENTRY DESCRIPTION PAYLOADS
// ============================================
export interface EntryDescriptionCreatePayload {
  entry_mgmt_id: number;
  name: string;
  slug: string;
  description?: string;
  rank_order: number;
  is_display: number; // IsActive enum value (0 or 1)
  article?: string; // JSON stringified content
  is_delete: boolean;
  layout_structure?: LayoutStructureItem[];
}

export type EntryDescriptionUpdatePayload = EntryDescriptionCreatePayload;

// ============================================
// SOCIAL PAYLOADS
// ============================================
export interface SocialCreatePayload {
  name: string;
  icon?: string;
  link: string;
  rank_order: number;
  is_display: number; // IsActive enum value (0 or 1)
  is_delete: boolean;
}

export type SocialUpdatePayload = SocialCreatePayload;

// ============================================
// SETTING LINK PAYLOADS
// ============================================
export interface SettingLinkCreatePayload {
  name: string;
  link: string;
  rank_order: number;
  is_active: number; // IsActive enum value (0 or 1)
  is_delete: boolean;
}

export type SettingLinkUpdatePayload = SettingLinkCreatePayload;

// ============================================
// ADMIN PAYLOADS
// ============================================
export interface AdminCreatePayload {
  email: string;
  user_name: string;
  password?: string;
  first_name: string;
  last_name: string;
  address?: string;
  phone_number?: string;
  birth?: string;
  gender?: number;
  status: number; // AdminStatus enum value
  is_active: number; // IsActive enum value (0 or 1)
  avatar?: string;
  department_ids?: number[];
  role_ids?: number[];
}

export interface AdminUpdatePayload extends Omit<AdminCreatePayload, 'password'> {
  password?: string;
}

// ============================================
// USER PAYLOADS
// ============================================
export interface UserCreatePayload {
  email: string;
  user_name: string;
  password?: string;
  first_name: string;
  last_name: string;
  address?: string;
  phone_number?: string;
  birth?: string;
  gender?: number;
  status: number; // UserStatus enum value
  is_active: number; // IsActive enum value (0 or 1)
  avatar?: string;
}

export interface UserUpdatePayload extends Omit<UserCreatePayload, 'password'> {
  password?: string;
}
