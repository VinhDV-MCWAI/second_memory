export const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export const API_ENDPOINTS = {
  AUTH: {
    CSRF_COOKIE: '/sanctum/csrf-cookie',
    LOGIN: '/admin/credential/login',
    LOGOUT: '/admin/credential/logout',
    ME: '/admin/credential/me',
  },
  MASTER: {
    ADMIN: '/admin/admin-mst',
    ROLE: '/admin/role-mst',
    FEATURE: '/admin/feature-mst',
    API: '/admin/api-mst',
    TOKEN: '/admin/token-mst',
  },
  JUNCTION: {
    ADMIN_ROLE: '/admin/admin-role-mst',
    API_ROLE: '/admin/api-role-mst',
  },
} as const;

export const ENDPOINTS = API_ENDPOINTS;
