import { describe, expect, it, beforeEach } from 'vitest';
import { http, HttpResponse } from 'msw';
import { apiClient } from './client';
import { API_ENDPOINTS } from '@/shared/api/endpoints';
import { apiUrl, envelope, errorEnvelope, server } from '@/test/server';

describe('apiClient', () => {
  beforeEach(() => localStorage.clear());

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

  it('refreshes the session once on 401 and retries the request', async () => {
    let calls = 0;
    let refreshes = 0;
    server.use(
      http.get(apiUrl('/admin/credential/me'), () => {
        calls += 1;
        return calls === 1
          ? HttpResponse.json(errorEnvelope(401, 'expired'), { status: 401 })
          : HttpResponse.json(envelope({ id: 7 }));
      }),
      http.post(apiUrl(API_ENDPOINTS.AUTH.REFRESH), () => {
        refreshes += 1;
        return HttpResponse.json(envelope(null));
      }),
    );

    const response = await apiClient.get<{ id: number }>('/admin/credential/me');

    expect(response.data).toEqual({ id: 7 });
    expect(refreshes).toBe(1);
    expect(calls).toBe(2);
  });

  it('does not try to refresh when the login itself returns 401', async () => {
    let refreshes = 0;
    server.use(
      http.post(apiUrl(API_ENDPOINTS.AUTH.LOGIN), () =>
        HttpResponse.json(errorEnvelope(401, 'bad credentials'), { status: 401 }),
      ),
      http.post(apiUrl(API_ENDPOINTS.AUTH.REFRESH), () => {
        refreshes += 1;
        return HttpResponse.json(envelope(null));
      }),
    );

    await expect(apiClient.post(API_ENDPOINTS.AUTH.LOGIN, {})).rejects.toMatchObject({
      response: { status: 401 },
    });
    expect(refreshes).toBe(0);
  });

  it('rejects when the refresh fails', async () => {
    server.use(
      http.get(apiUrl('/admin/credential/me'), () =>
        HttpResponse.json(errorEnvelope(401, 'expired'), { status: 401 }),
      ),
      http.post(apiUrl(API_ENDPOINTS.AUTH.REFRESH), () =>
        HttpResponse.json(errorEnvelope(401, 'refresh expired'), { status: 401 }),
      ),
    );

    await expect(apiClient.get('/admin/credential/me')).rejects.toMatchObject({
      response: { status: 401 },
    });
  });
});
