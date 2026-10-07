import { beforeEach, describe, expect, it, vi } from 'vitest';
import { act, renderHook, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { useCrud } from './use-crud';
import { useApiData } from './use-api-data';
import { notification } from '@/shared/utils/notification';
import { apiUrl, envelope, errorEnvelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';

vi.mock('next-intl', () => ({
  useTranslations: () => (key: string) => key,
}));
vi.mock('@/shared/utils/notification', () => ({
  notification: { success: vi.fn(), error: vi.fn(), info: vi.fn(), warning: vi.fn() },
}));

const ENDPOINT = '/admin/banner-mgmt';

describe('useCrud', () => {
  beforeEach(() => vi.clearAllMocks());

  it('creates via {endpoint}/store, toasts and invalidates the list', async () => {
    let body: unknown;
    server.use(
      http.post(apiUrl(`${ENDPOINT}/store`), async ({ request }) => {
        body = await request.json();
        return HttpResponse.json(envelope(5));
      }),
    );
    const { Wrapper, queryClient } = createQueryWrapper();
    const invalidate = vi.spyOn(queryClient, 'invalidateQueries');
    const { result } = renderHook(() => useCrud(ENDPOINT), { wrapper: Wrapper });

    let id: number | undefined;
    await act(async () => {
      id = await result.current.create({ title: 'Hello' });
    });

    expect(id).toBe(5);
    expect(body).toEqual({ title: 'Hello' });
    expect(notification.success).toHaveBeenCalledWith('createdSuccessfully');
    expect(invalidate).toHaveBeenCalledWith({ queryKey: [ENDPOINT] });
  });

  it('updates via PUT {endpoint}/update/{id} with the id in the body', async () => {
    let body: unknown;
    server.use(
      http.put(apiUrl(`${ENDPOINT}/update/9`), async ({ request }) => {
        body = await request.json();
        return HttpResponse.json(envelope(9));
      }),
    );
    const { Wrapper } = createQueryWrapper();
    const { result } = renderHook(() => useCrud(ENDPOINT), { wrapper: Wrapper });

    await act(async () => {
      await result.current.update(9, { title: 'New' });
    });

    expect(body).toEqual({ id: 9, title: 'New' });
    expect(notification.success).toHaveBeenCalledWith('updatedSuccessfully');
  });

  it('deletes via POST {endpoint}/delete with ids', async () => {
    let body: unknown;
    server.use(
      http.post(apiUrl(`${ENDPOINT}/delete`), async ({ request }) => {
        body = await request.json();
        return HttpResponse.json(envelope(null));
      }),
    );
    const { Wrapper } = createQueryWrapper();
    const { result } = renderHook(() => useCrud(ENDPOINT), { wrapper: Wrapper });

    await act(async () => {
      await result.current.remove([1, 2]);
    });

    expect(body).toEqual({ ids: [1, 2] });
    expect(notification.success).toHaveBeenCalledWith('deletedItemsSuccessfully');
  });

  it('toasts the server message on failure but leaves 422 to the form', async () => {
    server.use(
      http.post(apiUrl(`${ENDPOINT}/delete`), () =>
        HttpResponse.json(errorEnvelope(409, 'Cannot delete record(s) with existing admins'), {
          status: 409,
        }),
      ),
      http.post(apiUrl(`${ENDPOINT}/store`), () =>
        HttpResponse.json(errorEnvelope(422, { title: ['required'] }), { status: 422 }),
      ),
    );
    const { Wrapper } = createQueryWrapper();
    const { result } = renderHook(() => useCrud(ENDPOINT), { wrapper: Wrapper });

    await act(async () => {
      await expect(result.current.remove([1])).rejects.toBeTruthy();
    });
    expect(notification.error).toHaveBeenCalledWith('Cannot delete record(s) with existing admins');

    vi.mocked(notification.error).mockClear();
    await act(async () => {
      await expect(result.current.create({})).rejects.toBeTruthy();
    });
    expect(notification.error).not.toHaveBeenCalled();
  });

  it('refetches lists of the same resource after a mutation', async () => {
    let listCalls = 0;
    server.use(
      http.get(apiUrl(`${ENDPOINT}/list`), () => {
        listCalls++;
        return HttpResponse.json(envelope({ data: [] }));
      }),
      http.post(apiUrl(`${ENDPOINT}/store`), () => HttpResponse.json(envelope(1))),
    );
    const { Wrapper } = createQueryWrapper();
    const { result } = renderHook(
      () => ({ list: useApiData(ENDPOINT, { page: 2 }), crud: useCrud(ENDPOINT) }),
      { wrapper: Wrapper },
    );
    await waitFor(() => expect(result.current.list.loading).toBe(false));
    expect(listCalls).toBe(1);

    await act(async () => {
      await result.current.crud.create({ title: 'x' });
    });

    await waitFor(() => expect(listCalls).toBe(2));
  });
});
