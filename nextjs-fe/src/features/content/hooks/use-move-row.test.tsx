import { describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { useMoveRow } from './use-move-row';
import { apiUrl, envelope, errorEnvelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';
import { notification } from '@/shared/utils/notification';

vi.mock('next-intl', () => ({ useTranslations: () => (key: string) => key }));
vi.mock('@/shared/utils/notification', () => ({ notification: { error: vi.fn() } }));

describe('useMoveRow', () => {
  it('sends the direction and reloads the lists of the endpoint', async () => {
    let sent: unknown;
    server.use(
      http.put(apiUrl('/admin/article/move/4'), async ({ request }) => {
        sent = await request.json();
        return HttpResponse.json(envelope(null));
      }),
    );
    const { Wrapper, queryClient } = createQueryWrapper();
    const invalidate = vi.spyOn(queryClient, 'invalidateQueries');
    const { result } = renderHook(() => useMoveRow('/admin/article'), { wrapper: Wrapper });

    result.current.mutate({ id: 4, direction: 'up' });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(sent).toEqual({ direction: 'up' });
    expect(invalidate).toHaveBeenCalledWith({ queryKey: ['/admin/article'] });
  });

  it('shows the API error', async () => {
    server.use(
      http.put(apiUrl('/admin/article/move/4'), () =>
        HttpResponse.json(errorEnvelope(404, 'Not found'), { status: 404 }),
      ),
    );
    const { Wrapper } = createQueryWrapper();
    const { result } = renderHook(() => useMoveRow('/admin/article'), { wrapper: Wrapper });

    result.current.mutate({ id: 4, direction: 'down' });

    await waitFor(() => expect(notification.error).toHaveBeenCalled());
  });
});
