import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { EndUserForm } from './end-user-form';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';
import type { EndUser } from '@/shared/types/models';

vi.mock('next-intl', () => ({ useTranslations: () => (key: string) => key }));
vi.mock('@/shared/utils/notification', () => ({
  notification: { success: vi.fn(), error: vi.fn(), info: vi.fn(), warning: vi.fn() },
}));
vi.mock('@/features/history/components/history-viewer', () => ({ HistoryViewer: () => null }));

const endUser: EndUser = {
  id: 7,
  username: 'ann',
  display_name: 'Ann',
  is_active: true,
  saved_count: 2,
  last_login_at: null,
  created_at: '2026-10-01T09:00:00+00:00',
};

const renderForm = (onSuccess = vi.fn()) => {
  const { Wrapper } = createQueryWrapper();
  render(
    <Wrapper>
      <EndUserForm
        initialData={endUser}
        formId="test-form"
        onSuccess={onSuccess}
        onStateChange={vi.fn()}
      />
      <button type="submit" form="test-form">
        save
      </button>
    </Wrapper>,
  );
  return onSuccess;
};

const captureUpdate = () => {
  const sent: { body?: Record<string, unknown> } = {};
  server.use(
    http.put(apiUrl('/admin/end-user/update/7'), async ({ request }) => {
      sent.body = (await request.json()) as Record<string, unknown>;
      return HttpResponse.json(envelope(7));
    }),
  );
  return sent;
};

describe('EndUserForm', () => {
  it('locks without touching the password when the field is empty', async () => {
    const sent = captureUpdate();
    const onSuccess = renderForm();

    fireEvent.click(screen.getByLabelText('activeEndUser'));
    fireEvent.click(screen.getByRole('button', { name: 'save' }));

    await waitFor(() => expect(onSuccess).toHaveBeenCalled());
    expect(sent.body).toEqual({ id: 7, is_active: 0 });
  });

  it('sends a new password', async () => {
    const sent = captureUpdate();
    const onSuccess = renderForm();

    fireEvent.change(screen.getByLabelText('newPassword'), { target: { value: 'reset1234' } });
    fireEvent.click(screen.getByRole('button', { name: 'save' }));

    await waitFor(() => expect(onSuccess).toHaveBeenCalled());
    expect(sent.body).toEqual({ id: 7, is_active: 1, password: 'reset1234' });
  });

  it('refuses a password shorter than 8 characters before calling the API', async () => {
    const sent = captureUpdate();
    const onSuccess = renderForm();

    fireEvent.change(screen.getByLabelText('newPassword'), { target: { value: 'short' } });
    fireEvent.click(screen.getByRole('button', { name: 'save' }));

    expect(await screen.findByText(/password\.minLength/)).toBeTruthy();
    expect(sent.body).toBeUndefined();
    expect(onSuccess).not.toHaveBeenCalled();
  });
});
