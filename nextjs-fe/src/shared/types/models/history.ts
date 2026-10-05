/**
 * History Model Types
 * Auto-generated from back-end API history models
 */

// Base History Interface
export interface BaseHistory {
  id: number;
  action: 'create' | 'update' | 'delete';
  changed_by: number; // Admin ID who made the change
  changed_at: string;
  old_values?: Record<string, unknown>;
  new_values?: Record<string, unknown>;
  ip_address?: string;
  user_agent?: string;
}

// ============================================
// MASTER HISTORY MODELS
// ============================================

export interface AdminMstHist extends BaseHistory {
  admin_mst_id: number;
}

export interface RoleMstHist extends BaseHistory {
  role_mst_id: number;
}

export interface DepartmentMstHist extends BaseHistory {
  department_mst_id: number;
}

export interface ApiMstHist extends BaseHistory {
  api_mst_id: number;
}

export interface FeatureMstHist extends BaseHistory {
  feature_mst_id: number;
}



// ============================================
// MANAGEMENT HISTORY MODELS
// ============================================

export interface UserMgmtHist extends BaseHistory {
  user_mgmt_id: number;
}

export interface CategoryMgmtHist extends BaseHistory {
  category_mgmt_id: number;
}

export interface EntryMgmtHist extends BaseHistory {
  entry_mgmt_id: number;
}

export interface EntryDescriptionMgmtHist extends BaseHistory {
  entry_description_mgmt_id: number;
}

export interface BannerMgmtHist extends BaseHistory {
  banner_mgmt_id: number;
}

export interface SliderMgmtHist extends BaseHistory {
  slider_mgmt_id: number;
}

export interface SocialMgmtHist extends BaseHistory {
  social_mgmt_id: number;
}

export interface SettingLinkMgmtHist extends BaseHistory {
  setting_link_mgmt_id: number;
}

// ============================================
// HISTORY DIFF TYPE
// ============================================

export interface HistoryDiff {
  field: string;
  oldValue: unknown;
  newValue: unknown;
  label?: string;
}

// ============================================
// HISTORY FILTER OPTIONS
// ============================================

export interface HistoryFilterOptions {
  action?: 'create' | 'update' | 'delete';
  changed_by?: number;
  from_date?: string;
  to_date?: string;
  page?: number;
  per_page?: number;
}
