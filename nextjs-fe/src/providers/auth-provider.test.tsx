import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { act, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { AuthProvider, USER_REFRESH_MIN_INTERVAL_MS } from './auth-provider';
import { useAuth } from './use-auth';
import { apiClient } from '@/shared/api/client';
import { API_ENDPOINTS } from '@/shared/api/endpoints';
import { apiUrl, envelope, errorEnvelope, server } from '@/test/server';
import type { User } from '@/shared/types';

const push = vi.fn();
const router = { push };
vi.mock('next/navigation', () => ({ useRouter: () => router }));

const viewer = (permissions: string[]) =>
  ({ id: 3, user_name: 'viewer', roles: [{ id: 2, name: 'viewer' }], permissions }) as User;

let current = viewer(['skill.list']);

function Permissions() {
  const { user, isAuthenticated } = useAuth();
  return <p>{isAuthenticated ? `can: ${user?.permissions.join(',')}` : 'signed out'}</p>;
}

/** Renders the provider and waits until it is signed in and listening for changes. */
async function renderSignedIn() {
  const onForbidden = vi.spyOn(apiClient, 'onForbidden');
  render(
    <AuthProvider>
      <Permissions />
    </AuthProvider>,
  );
  await screen.findByText('can: skill.list');
  await waitFor(() => expect(onForbidden).toHaveBeenLastCalledWith(expect.any(Function)));
}

function returnToTab() {
  act(() => {
    vi.setSystemTime(Date.now() + USER_REFRESH_MIN_INTERVAL_MS);
    document.dispatchEvent(new Event('visibilitychange'));
  });
}

describe('AuthProvider permission refresh', () => {
  beforeEach(() => {
    vi.useFakeTimers({ toFake: ['Date'] });
    current = viewer(['skill.list']);
    server.use(http.get(apiUrl(API_ENDPOINTS.AUTH.ME), () => HttpResponse.json(envelope(current))));
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
    push.mockClear();
  });

  it('reloads the permissions when the admin comes back to the tab', async () => {
    await renderSignedIn();

    current = viewer(['skill.list', 'skill.store']);
    returnToTab();

    await screen.findByText('can: skill.list,skill.store');
  });

  it('reloads the permissions after a 403, at most once per interval', async () => {
    let calls = 0;
    server.use(
      http.get(apiUrl(API_ENDPOINTS.AUTH.ME), () => {
        calls += 1;
        return HttpResponse.json(envelope(current));
      }),
      http.post(apiUrl('/admin/skill/delete'), () =>
        HttpResponse.json(errorEnvelope(403, 'Access is forbidden'), { status: 403 }),
      ),
    );
    await renderSignedIn();
    vi.setSystemTime(Date.now() + USER_REFRESH_MIN_INTERVAL_MS);

    current = viewer([]);
    await Promise.allSettled([
      apiClient.post('/admin/skill/delete', { ids: [1] }),
      apiClient.post('/admin/skill/delete', { ids: [2] }),
    ]);

    await screen.findByText('can:');
    expect(calls).toBe(2);
  });

  it('signs out when the session is gone on refresh', async () => {
    await renderSignedIn();

    server.use(
      http.get(apiUrl(API_ENDPOINTS.AUTH.ME), () =>
        HttpResponse.json(errorEnvelope(401, 'no session'), { status: 401 }),
      ),
    );
    returnToTab();

    await screen.findByText('signed out');
    expect(push).toHaveBeenCalled();
  });
});
