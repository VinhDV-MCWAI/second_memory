import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { DepartmentForm } from './department-form';
import { apiUrl, envelope, errorEnvelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';
import { ValidationRules } from '@/shared/validation/validation-rules';

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

  it('checks a field once it is left and keeps saving off until every field passes', async () => {
    const onStateChange = vi.fn();
    const { Wrapper } = createQueryWrapper();
    render(
      <Wrapper>
        <DepartmentForm formId="test-form" onSuccess={vi.fn()} onStateChange={onStateChange} />
      </Wrapper>,
    );
    const code = screen.getByLabelText(/code/);
    const lastState = () => onStateChange.mock.lastCall?.[0];

    await waitFor(() => expect(lastState()).toMatchObject({ submitDisabled: true }));
    // Nothing is flagged before the user leaves the field
    expect(screen.queryByText('departmentCode.required')).toBeNull();
    fireEvent.blur(code);
    expect(await screen.findByText('departmentCode.required')).toBeTruthy();

    // Then every change is checked, without saving
    fireEvent.change(code, { target: { value: 'HR' } });
    await waitFor(() => expect(screen.queryByText('departmentCode.required')).toBeNull());
    fireEvent.change(screen.getByLabelText(/name/), { target: { value: 'Human resources' } });
    await waitFor(() => expect(lastState()).toMatchObject({ submitDisabled: false }));
  });

  it('shows at once what a saved record breaks', async () => {
    const { Wrapper } = createQueryWrapper();
    server.use(
      http.get(apiUrl('/admin/audit-log/list'), () =>
        HttpResponse.json(
          envelope({ data: [], current_page: 1, last_page: 1, per_page: 15, total: 0 }),
        ),
      ),
    );
    render(
      <Wrapper>
        <DepartmentForm
          formId="test-form"
          initialData={{
            id: 1,
            code: 'X'.repeat(ValidationRules.DEPARTMENT_CODE_MAX + 1),
            name: 'Old',
            description: null,
            is_active: true,
            admin_count: 0,
            updated_at: null,
          }}
          onSuccess={vi.fn()}
          onStateChange={vi.fn()}
        />
      </Wrapper>,
    );
    expect(await screen.findByText('departmentCode.maxLength')).toBeTruthy();
  });
});
