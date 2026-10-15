import { describe, expect, it, afterEach } from 'vitest';
import { http, HttpResponse } from 'msw';
import { apiClient } from './client';
import { API_ENDPOINTS } from '@/shared/api/endpoints';
import { apiUrl, envelope, errorEnvelope, server } from '@/test/server';

describe('apiClient', () => {
  afterEach(() => {
    apiClient.onUnauthorized(null);
    apiClient.onForbidden(null);
  });

  it('returns the response envelope and sends query params', async () => {
    let seenPage: string | null = null;
    server.use(
      http.get(apiUrl('/admin/role-mst/list'), ({ request }) => {
        seenPage = new URL(request.url).searchParams.get('page');
        return HttpResponse.json(envelope({ data: [{ id: 1 }] }));
      }),
    );

    const response = await apiClient.get<{ data: { id: number }[] }>('/admin/role-mst/list', {
      params: { page: 2 },
    });

    expect(seenPage).toBe('2');
    expect(response.data.data).toEqual([{ id: 1 }]);
    expect(response.error.status).toBe(false);
  });

  it('rejects with the server envelope on HTTP errors', async () => {
    server.use(
      http.post(apiUrl('/admin/role-mst/store'), () =>
        HttpResponse.json(errorEnvelope(422, { name: ['required'] }), { status: 422 }),
      ),
    );

    await expect(apiClient.post('/admin/role-mst/store', {})).rejects.toMatchObject({
      response: { status: 422, data: { error: { messages: { name: ['required'] } } } },
    });
  });

  it('calls the unauthorized handler on a 401 from a normal endpoint', async () => {
    let signedOut = 0;
    apiClient.onUnauthorized(() => {
      signedOut += 1;
    });
    server.use(
      http.get(apiUrl('/admin/role-mst/list'), () =>
        HttpResponse.json(errorEnvelope(401, 'Unauthorized Access'), { status: 401 }),
      ),
    );

    await expect(apiClient.get('/admin/role-mst/list')).rejects.toMatchObject({
      response: { status: 401 },
    });
    expect(signedOut).toBe(1);
  });

  it('leaves a 401 from the auth endpoints to the caller', async () => {
    let signedOut = 0;
    apiClient.onUnauthorized(() => {
      signedOut += 1;
    });
    server.use(
      http.post(apiUrl(API_ENDPOINTS.AUTH.LOGIN), () =>
        HttpResponse.json(errorEnvelope(401, 'bad credentials'), { status: 401 }),
      ),
      http.get(apiUrl(API_ENDPOINTS.AUTH.ME), () =>
        HttpResponse.json(errorEnvelope(401, 'no session'), { status: 401 }),
      ),
    );

    await expect(apiClient.post(API_ENDPOINTS.AUTH.LOGIN, {})).rejects.toMatchObject({
      response: { status: 401 },
    });
    await expect(apiClient.get(API_ENDPOINTS.AUTH.ME)).rejects.toMatchObject({
      response: { status: 401 },
    });
    expect(signedOut).toBe(0);
  });

  it('does not sign out on 403 (signed in but not allowed)', async () => {
    let signedOut = 0;
    apiClient.onUnauthorized(() => {
      signedOut += 1;
    });
    server.use(
      http.get(apiUrl('/admin/role-mst/list'), () =>
        HttpResponse.json(errorEnvelope(403, 'Access is forbidden'), { status: 403 }),
      ),
    );

    await expect(apiClient.get('/admin/role-mst/list')).rejects.toMatchObject({
      response: { status: 403 },
    });
    expect(signedOut).toBe(0);
  });

  it('calls the forbidden handler on a 403 so the permissions can be reloaded', async () => {
    let forbidden = 0;
    apiClient.onForbidden(() => {
      forbidden += 1;
    });
    server.use(
      http.post(apiUrl('/admin/role-mst/delete'), () =>
        HttpResponse.json(errorEnvelope(403, 'Access is forbidden'), { status: 403 }),
      ),
    );

    await expect(apiClient.post('/admin/role-mst/delete', { ids: [1] })).rejects.toMatchObject({
      response: { status: 403 },
    });
    expect(forbidden).toBe(1);
  });

  it('fetches a fresh CSRF cookie and retries once on a 419', async () => {
    let attempts = 0;
    let csrfFetched = 0;
    server.use(
      http.get(apiUrl(API_ENDPOINTS.AUTH.CSRF_COOKIE), () => {
        csrfFetched += 1;
        return new HttpResponse(null, { status: 204 });
      }),
      http.post(apiUrl('/admin/role-mst/delete'), () => {
        attempts += 1;
        return attempts === 1
          ? HttpResponse.json(errorEnvelope(419, 'CSRF token mismatch'), { status: 419 })
          : HttpResponse.json(envelope(1));
      }),
    );

    const response = await apiClient.post<number>('/admin/role-mst/delete', { ids: [1] });

    expect(response.data).toBe(1);
    expect(attempts).toBe(2);
    expect(csrfFetched).toBe(1);
  });

  it('gives up after one retry when the CSRF token still does not match', async () => {
    server.use(
      http.get(
        apiUrl(API_ENDPOINTS.AUTH.CSRF_COOKIE),
        () => new HttpResponse(null, { status: 204 }),
      ),
      http.post(apiUrl('/admin/role-mst/delete'), () =>
        HttpResponse.json(errorEnvelope(419, 'CSRF token mismatch'), { status: 419 }),
      ),
    );

    await expect(apiClient.post('/admin/role-mst/delete', { ids: [1] })).rejects.toMatchObject({
      response: { status: 419 },
    });
  });
});
