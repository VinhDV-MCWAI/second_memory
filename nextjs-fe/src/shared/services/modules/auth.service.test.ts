import { afterEach, describe, expect, it } from 'vitest';
import { http, HttpResponse } from 'msw';
import { authService } from './auth.service';
import { API_ENDPOINTS } from '@/shared/api/endpoints';
import { apiUrl, envelope, server } from '@/test/server';

describe('authService', () => {
  afterEach(() => authService.releaseRefreshLock());

  it('posts credentials and returns the payload', async () => {
    let body: unknown;
    server.use(
      http.post(apiUrl(API_ENDPOINTS.AUTH.LOGIN), async ({ request }) => {
        body = await request.json();
        return HttpResponse.json(envelope({ user: { id: 1 } }));
      }),
    );

    const data = await authService.login({ user_name: 'admin', password: 'secret' });

    expect(body).toEqual({ user_name: 'admin', password: 'secret' });
    expect(data).toEqual({ user: { id: 1 } });
  });

  it('calls logout, refresh and me', async () => {
    const hits: string[] = [];
    server.use(
      http.post(apiUrl(API_ENDPOINTS.AUTH.LOGOUT), () => {
        hits.push('logout');
        return HttpResponse.json(envelope(null));
      }),
      http.post(apiUrl(API_ENDPOINTS.AUTH.REFRESH), () => {
        hits.push('refresh');
        return HttpResponse.json(envelope({ expires_in: 60 }));
      }),
      http.get(apiUrl(API_ENDPOINTS.AUTH.ME), () => {
        hits.push('me');
        return HttpResponse.json(envelope({ user: { id: 2 } }));
      }),
    );

    await authService.logout();
    expect(await authService.refreshToken()).toEqual({ expires_in: 60 });
    expect(await authService.getMe()).toEqual({ user: { id: 2 } });
    expect(hits).toEqual(['logout', 'refresh', 'me']);
  });

  it('guards refreshes with the shared lock', async () => {
    expect(await authService.acquireRefreshLock()).toBe(true);
    expect(authService.isRefreshLocked()).toBe(true);
    expect(await authService.acquireRefreshLock()).toBe(false);

    authService.releaseRefreshLock();
    expect(authService.isRefreshLocked()).toBe(false);
  });
});
