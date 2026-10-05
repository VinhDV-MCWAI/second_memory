export const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export const API_ENDPOINTS = {
  AUTH: {
    LOGIN: '/admin/credential/login',
    LOGOUT: '/admin/credential/trust/logout',
    REFRESH: '/admin/credential/trust/refresh-token',
    ME: '/admin/credential/me',
  },
  MASTER: {
    ADMIN: '/admin/admin-mst',
    ROLE: '/admin/role-mst',
    DEPARTMENT: '/admin/department-mst',
    FEATURE: '/admin/feature-mst',
    API: '/admin/api-mst',
    TOKEN: '/admin/token-mst',
    POLICY_DEPARTMENT: '/admin/policy-department-mst',
  },
  MANAGEMENT: {
    BANNER: '/admin/banner-mgmt',
    CATEGORY: '/admin/category-mgmt',
    ENTRY: '/admin/entry-mgmt',
    ENTRY_DESCRIPTION: '/admin/entry-description-mgmt',
    SLIDER: '/admin/slider-mgmt',
    SOCIAL: '/admin/social-mgmt',
    USER: '/admin/user-mgmt',
    SETTING_LINK: '/admin/setting-link-mgmt',
  },
  JUNCTION: {
    ADMIN_ROLE: '/admin/admin-role-mst',
    ADMIN_DEPARTMENT: '/admin/admin-department-mst',
    API_ROLE: '/admin/api-role-mst',
    DEPARTMENT_MANAGEMENT: '/admin/department-management-mst',
    CATEGORY_ENTRY: '/admin/category-entry-mgmt',
  },
  MEDIA: {
    FILES: '/admin/media-mgmt',
    UPLOAD: '/admin/media-mgmt/store',
  },
} as const;

export const ENDPOINTS = API_ENDPOINTS;
