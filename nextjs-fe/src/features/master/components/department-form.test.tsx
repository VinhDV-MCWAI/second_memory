import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { DepartmentForm } from './department-form';
import { apiUrl, envelope, errorEnvelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';

vi.mock('next-intl', () => ({ useTranslations: () => (key: string) => key }));
vi.mock('@/shared/utils/notification', () => ({
  notification: { success: vi.fn(), error: vi.fn(), info: vi.fn(), warning: vi.fn() },
}));

const renderForm = (onSuccess = vi.fn()) => {
  const { Wrapper } = createQueryWrapper();
  render(
    <Wrapper>
      <DepartmentForm formId="test-form" onSuccess={onSuccess} onStateChange={vi.fn()} />
      <button type="submit" form="test-form">
        save
      </button>
    </Wrapper>,
  );
  fireEvent.change(screen.getByLabelText(/code/), { target: { value: 'HR' } });
  fireEvent.change(screen.getByLabelText(/name/), { target: { value: 'Human resources' } });
  fireEvent.click(screen.getByRole('button', { name: 'save' }));
  return onSuccess;
};

describe('DepartmentForm', () => {
  it('sends an empty description as null and active as 1', async () => {
    let body: Record<string, unknown> | undefined;
    server.use(
      http.post(apiUrl('/admin/department/store'), async ({ request }) => {
        body = (await request.json()) as Record<string, unknown>;
        return HttpResponse.json(envelope(1));
      }),
    );

    const onSuccess = renderForm();

    await waitFor(() => expect(onSuccess).toHaveBeenCalled());
    expect(body).toEqual({ code: 'HR', name: 'Human resources', description: null, is_active: 1 });
  });

  it('shows a taken code on the code field', async () => {
    server.use(
      http.post(apiUrl('/admin/department/store'), () =>
        HttpResponse.json(errorEnvelope(422, { code: ['The code has already been taken.'] }), {
          status: 422,
        }),
      ),
    );

    const onSuccess = renderForm();

    expect(await screen.findByText('The code has already been taken.')).toBeTruthy();
    expect(onSuccess).not.toHaveBeenCalled();
  });
});
