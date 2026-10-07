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
    FEATURE: '/admin/feature-mst',
    API: '/admin/api-mst',
    TOKEN: '/admin/token-mst',
  },
  MANAGEMENT: {
    CATEGORY: '/admin/category-mgmt',
    ENTRY: '/admin/entry-mgmt',
    ENTRY_DESCRIPTION: '/admin/entry-description-mgmt',
  },
  JUNCTION: {
    ADMIN_ROLE: '/admin/admin-role-mst',
    API_ROLE: '/admin/api-role-mst',
    CATEGORY_ENTRY: '/admin/category-entry-mgmt',
  },
  MEDIA: {
    FILES: '/admin/media-mgmt',
    UPLOAD: '/admin/media-mgmt/store',
  },
} as const;

export const ENDPOINTS = API_ENDPOINTS;
