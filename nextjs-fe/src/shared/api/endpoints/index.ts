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
    PERMISSION: '/admin/permission/list',
    ADMIN_SESSION: '/admin/admin-session',
  },
  AUDIT: {
    LOG: '/admin/audit-log',
  },
  LEDGER: {
    SKILL: '/admin/skill',
    SKILL_LEVEL: '/admin/skill-level',
    TAG: '/admin/tag',
    EVIDENCE: '/admin/evidence',
    LEARNING_GOAL: '/admin/learning-goal',
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
