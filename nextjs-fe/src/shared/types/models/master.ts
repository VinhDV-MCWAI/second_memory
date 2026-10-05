/**
 * Master Data Model Types
 * Auto-generated from back-end API models
 */

// ============================================
// ADMIN MASTER
// ============================================
export interface AdminMst {
  id: number;
  email: string;
  user_name: string;
  password?: string; // Only for create/update
  first_name: string;
  last_name: string;
  address?: string;
  phone_number?: string;
  birth?: string;
  gender?: number; // 0: Female, 1: Male, 2: Other
  status: number;
  is_active: boolean;
  avatar?: string;
  email_verified_at?: string;
  is_delete: boolean;
  remember_token?: string;
  created_at: string;
  updated_at: string;
  // Relations
  roles?: RoleMst[];
  departments?: DepartmentMst[];
}

// ============================================
// ROLE MASTER
// ============================================
export interface RoleMst {
  id: number;
  name: string;
  description?: string;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  created_at: string;
  updated_at: string;
  // Relations
  admins?: AdminMst[];
  apis?: ApiMst[];
}

// ============================================
// DEPARTMENT MASTER
// ============================================
export interface DepartmentMst {
  id: number;
  name: string;
  description?: string;
  parent_id?: number;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  created_at: string;
  updated_at: string;
  // Relations
  parent?: DepartmentMst;
  children?: DepartmentMst[];
  admins?: AdminMst[];
}

// ============================================
// API MASTER
// ============================================
export interface ApiMst {
  id: number;
  path: string;
  method: string; // GET, POST, PUT, DELETE
  description?: string;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  created_at: string;
  updated_at: string;
  // Relations
  roles?: RoleMst[];
}

// ============================================
// FEATURE MASTER
// ============================================
export interface FeatureMst {
  id: number;
  name: string;
  code: string;
  description?: string;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  created_at: string;
  updated_at: string;
}

// ============================================
// LANGUAGE MASTER
// ============================================


// ============================================
// TOKEN MASTER
// ============================================
export interface TokenMst {
  id: number;
  token_hash: string;
  account_id: number;
  account_type: string; // admin, user
  expires_at: string;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  created_at: string;
  updated_at: string;
}

// ============================================
// POLICY DEPARTMENT MASTER
// ============================================
export interface PolicyDepartmentMst {
  id: number;
  name: string;
  description?: string;
  status: number;
  is_active: boolean;
  is_delete: boolean;
  created_at: string;
  updated_at: string;
}

// ============================================
// ORIGINAL TRANSLATOR MASTER
// ============================================


// ============================================
// JUNCTION TABLES
// ============================================
export interface AdminDepartmentMst {
  id: number;
  admin_mst_id: number;
  department_mst_id: number;
  created_at: string;
  updated_at: string;
}

export interface AdminRoleMst {
  id: number;
  admin_mst_id: number;
  role_mst_id: number;
  created_at: string;
  updated_at: string;
}

export interface ApiRoleMst {
  id: number;
  api_mst_id: number;
  role_mst_id: number;
  created_at: string;
  updated_at: string;
}

export interface DepartmentManagementMst {
  id: number;
  department_mst_id: number;
  management_id: number;
  management_type: string;
  created_at: string;
  updated_at: string;
}


