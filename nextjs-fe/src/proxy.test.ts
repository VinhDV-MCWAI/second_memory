import { describe, expect, it } from 'vitest';
import { NextRequest } from 'next/server';
import { proxy } from './proxy';
import { SESSION_COOKIE_NAME } from '@/shared/config/constant';

describe('proxy (admin route guard)', () => {
  it('redirects to the login page with the requested path when there is no session cookie', () => {
    const response = proxy(new NextRequest('http://localhost/admin/admins?page=2'));

    expect(response.status).toBe(307);
    const location = new URL(response.headers.get('location') ?? '');
    expect(location.pathname).toBe('/login');
    expect(location.searchParams.get('redirect')).toBe('/admin/admins?page=2');
  });

  it('lets the request through when the session cookie is present', () => {
    const request = new NextRequest('http://localhost/admin', {
      headers: { cookie: `${SESSION_COOKIE_NAME}=abc` },
    });

    const response = proxy(request);

    expect(response.headers.get('location')).toBeNull();
    expect(response.headers.get('x-middleware-next')).toBe('1');
  });
});
