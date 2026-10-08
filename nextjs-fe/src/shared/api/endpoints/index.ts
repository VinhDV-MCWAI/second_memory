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
  },
  AUDIT: {
    LOG: '/admin/audit-log',
  },
  LEDGER: {
    SKILL: '/admin/skill',
    SKILL_LEVEL: '/admin/skill-level',
    TAG: '/admin/tag',
  },
} as const;

export const ENDPOINTS = API_ENDPOINTS;
