import { NextResponse, type NextRequest } from 'next/server';
import { ADMIN_ROUTES, REMEMBER_COOKIE_NAME, SESSION_COOKIE_NAME } from '@/shared/config/constant';

/**
 * Route guard for /admin/* (not the login and error pages): without a session cookie (or the cookie of a remembered device), go to the login page before any admin
 * page renders (no flash of protected UI). This is only a presence check; the API still
 * validates the session on every request and the auth provider handles a 401.
 */
export function proxy(request: NextRequest): NextResponse {
  if (request.cookies.has(SESSION_COOKIE_NAME) || request.cookies.has(REMEMBER_COOKIE_NAME)) {
    return NextResponse.next();
  }

  const loginUrl = new URL(ADMIN_ROUTES.LOGIN, request.url);
  loginUrl.searchParams.set('redirect', request.nextUrl.pathname + request.nextUrl.search);

  return NextResponse.redirect(loginUrl);
}

export const config = {
  // Everything under /admin except its sign-in page and error pages (ADR-0021)
  matcher: ['/admin', '/admin/((?!login|401|500).*)'],
};
