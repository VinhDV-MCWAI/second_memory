import { NextResponse, type NextRequest } from 'next/server';
import { ADMIN_ROUTES, SESSION_COOKIE_NAME } from '@/shared/config/constant';

/**
 * Route guard for /admin/*: without a session cookie, go to the login page before any admin
 * page renders (no flash of protected UI). This is only a presence check; the API still
 * validates the session on every request and the auth provider handles a 401.
 */
export function proxy(request: NextRequest): NextResponse {
  if (request.cookies.has(SESSION_COOKIE_NAME)) {
    return NextResponse.next();
  }

  const loginUrl = new URL(ADMIN_ROUTES.LOGIN, request.url);
  loginUrl.searchParams.set('redirect', request.nextUrl.pathname + request.nextUrl.search);

  return NextResponse.redirect(loginUrl);
}

export const config = {
  matcher: ['/admin', '/admin/:path*'],
};
