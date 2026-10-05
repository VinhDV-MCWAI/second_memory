/**
 * Management Data Model Types
 * Auto-generated from back-end API models
 */

// ============================================
// USER MANAGEMENT
// ============================================
export interface UserMgmt {
  id: number;
  email: string;
  user_name: string;
  password?: string;
  first_name: string;
  last_name: string;
  address?: string;
  phone_number?: string;
  birth?: string;
  gender?: number;
  avatar?: string;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  email_verified_at?: string;
  created_at: string;
  updated_at: string;
}

// ============================================
// CATEGORY MANAGEMENT
// ============================================
export interface CategoryMgmt {
  id: number;
  name: string;
  slug: string;
  description?: string;
  image?: string;
  order: number;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  created_at: string;
  updated_at: string;
  // Relations
  entries?: EntryMgmt[];
}

// ============================================
// ENTRY MANAGEMENT
// ============================================
export interface EntryMgmt {
  id: number;
  name: string;
  slug: string;
  description?: string;
  icon?: string;
  level?: number; // 1-5
  status: number;
  is_active: boolean;
  is_delete: boolean;
  created_at: string;
  updated_at: string;
  // Relations
  categories?: CategoryMgmt[];
  descriptions?: EntryDescriptionMgmt[];
}

// ============================================
// ENTRY DESCRIPTION MANAGEMENT
// ============================================
export interface EntryDescriptionMgmt {
  id: number;
  entry_mgmt_id: number;
  title: string;
  content: string; // Rich text
  order: number;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  created_at: string;
  updated_at: string;
  // Relations
  entry?: EntryMgmt;
}

// ============================================
// BANNER MANAGEMENT
// ============================================
export interface BannerMgmt {
  id: number;
  title: string;
  subtitle?: string;
  image: string;
  link?: string;
  target?: string; // _self, _blank
  order: number;
  start_date?: string;
  end_date?: string;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  created_at: string;
  updated_at: string;
}

// ============================================
// SLIDER MANAGEMENT
// ============================================
export interface SliderMgmt {
  id: number;
  title: string;
  subtitle?: string;
  description?: string;
  image: string;
  link?: string;
  target?: string;
  order: number;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  created_at: string;
  updated_at: string;
}

// ============================================
// SOCIAL MANAGEMENT
// ============================================
export interface SocialMgmt {
  id: number;
  name: string;
  icon: string;
  link: string;
  order: number;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  created_at: string;
  updated_at: string;
}

// ============================================
// SETTING LINK MANAGEMENT
// ============================================
export interface SettingLinkMgmt {
  id: number;
  key: string;
  value: string;
  description?: string;
  type: string; // text, url, email, etc.
  group?: string;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  created_at: string;
  updated_at: string;
}

// ============================================
// CATEGORY ENTRY JUNCTION
// ============================================
export interface CategoryEntryMgmt {
  id: number;
  category_mgmt_id: number;
  entry_mgmt_id: number;
  created_at: string;
  updated_at: string;
}
