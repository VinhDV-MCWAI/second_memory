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
    ROLE: '/admin/role',
    DEPARTMENT: '/admin/department',
    END_USER: '/admin/end-user',
    PERMISSION: '/admin/permission/list',
    ADMIN_SESSION: '/admin/admin-session',
  },
  AUDIT: {
    LOG: '/admin/audit-log',
  },
  CONTENT: {
    CATEGORY: '/admin/content-category',
    ARTICLE: '/admin/article',
    SEARCH: '/admin/search',
    DASHBOARD: '/admin/dashboard/summary',
  },
  MEDIA: {
    BASE: '/admin/media',
    MULTIPART: '/admin/media/multipart',
  },
  SYSTEM: {
    BACKGROUND_TASK: '/admin/background-task',
    BROADCASTING_AUTH: '/broadcasting/auth',
  },
} as const;

export const ENDPOINTS = API_ENDPOINTS;
