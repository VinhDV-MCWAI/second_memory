import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';
import { SessionList } from './session-list';

vi.mock('next-intl', () => {
  const t = (key: string, values?: Record<string, unknown>) =>
    values ? `${key} ${JSON.stringify(values)}` : key;
  return { useTranslations: () => t };
});
const notification = vi.hoisted(() => ({ success: vi.fn(), error: vi.fn() }));
vi.mock('@/shared/utils/notification', () => ({ notification }));

const CHROME_WINDOWS =
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36';
const FIREFOX_LINUX = 'Mozilla/5.0 (X11; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0';

const session = (id: number, userAgent: string, isCurrent: boolean) => ({
  id,
  ip_address: `10.0.0.${id}`,
  user_agent: userAgent,
  signed_in_at: '2026-10-15T08:00:00+00:00',
  last_used_at: '2026-10-15T09:00:00+00:00',
  is_current: isCurrent,
});

function renderList() {
  const { Wrapper } = createQueryWrapper();
  return render(<SessionList />, { wrapper: Wrapper });
}

describe('SessionList', () => {
  let rows: unknown[] = [];

  beforeEach(() => {
    rows = [session(1, CHROME_WINDOWS, true), session(2, FIREFOX_LINUX, false)];
    vi.clearAllMocks();
    server.use(
      http.get(apiUrl('/admin/admin-session/list'), () =>
        HttpResponse.json(envelope({ data: rows })),
      ),
    );
  });

  it('names each device and marks this one, which has no sign out button', async () => {
    renderList();

    await screen.findByText('deviceName {"browser":"Chrome","os":"Windows"}');
    screen.getByText('deviceName {"browser":"Firefox","os":"Linux"}');
    screen.getByText('thisDevice');
    screen.getByText('10.0.0.2');
    expect(screen.getAllByRole('button', { name: 'signOut' })).toHaveLength(1);
  });

  it('signs out another device after confirming', async () => {
    const ended = vi.fn();
    server.use(
      http.post(apiUrl('/admin/admin-session/delete'), async ({ request }) => {
        ended(await request.json());
        rows = [session(1, CHROME_WINDOWS, true)];
        return HttpResponse.json(envelope(1));
      }),
    );
    renderList();
    await screen.findByText('10.0.0.2');

    fireEvent.click(screen.getByRole('button', { name: 'signOut' }));
    fireEvent.click(await screen.findByRole('button', { name: 'signOut' }));

    await waitFor(() => expect(ended).toHaveBeenCalledWith({ ids: [2] }));
    expect(notification.success).toHaveBeenCalledWith('signedOut {"count":1}');
    await screen.findByText('onlyThisDevice');
  });

  it('signs out every other device after confirming', async () => {
    const endedOthers = vi.fn();
    server.use(
      http.post(apiUrl('/admin/admin-session/delete-others'), () => {
        endedOthers();
        return HttpResponse.json(envelope(1));
      }),
    );
    renderList();
    await screen.findByText('10.0.0.2');

    fireEvent.click(screen.getByRole('button', { name: 'signOutOthers' }));
    fireEvent.click(await screen.findByRole('button', { name: 'signOutOthers' }));

    await waitFor(() => expect(endedOthers).toHaveBeenCalledOnce());
  });

  it('offers nothing to sign out when only this device is signed in', async () => {
    rows = [session(1, CHROME_WINDOWS, true)];
    renderList();

    await screen.findByText('onlyThisDevice');
    expect(screen.queryByRole('button', { name: 'signOutOthers' })).toBeNull();
  });
});
