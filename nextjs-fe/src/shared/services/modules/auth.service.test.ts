import { describe, expect, it } from 'vitest';
import { http, HttpResponse } from 'msw';
import { authService } from './auth.service';
import { API_ENDPOINTS } from '@/shared/api/endpoints';
import { apiUrl, envelope, server } from '@/test/server';

describe('authService', () => {
  it('fetches the CSRF cookie, then posts the credentials and returns the admin', async () => {
    const hits: string[] = [];
    let body: unknown;
    server.use(
      http.get(apiUrl(API_ENDPOINTS.AUTH.CSRF_COOKIE), () => {
        hits.push('csrf');
        return new HttpResponse(null, { status: 204 });
      }),
      http.post(apiUrl(API_ENDPOINTS.AUTH.LOGIN), async ({ request }) => {
        hits.push('login');
        body = await request.json();
        return HttpResponse.json(envelope({ id: 1, user_name: 'admin' }));
      }),
    );

    const user = await authService.login({ user_name: 'admin', password: 'secret' });

    expect(hits).toEqual(['csrf', 'login']);
    expect(body).toEqual({ user_name: 'admin', password: 'secret' });
    expect(user).toEqual({ id: 1, user_name: 'admin' });
  });

  it('calls logout and me', async () => {
    const hits: string[] = [];
    server.use(
      http.post(apiUrl(API_ENDPOINTS.AUTH.LOGOUT), () => {
        hits.push('logout');
        return HttpResponse.json(envelope(null));
      }),
      http.get(apiUrl(API_ENDPOINTS.AUTH.ME), () => {
        hits.push('me');
        return HttpResponse.json(envelope({ id: 2 }));
      }),
    );

    await authService.logout();
    expect(await authService.getMe()).toEqual({ id: 2 });
    expect(hits).toEqual(['logout', 'me']);
  });
});
