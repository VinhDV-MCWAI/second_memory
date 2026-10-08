import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { LearningGoalForm } from './learning-goal-form';
import { apiUrl, envelope, errorEnvelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';
import type { LearningGoal } from '@/shared/types/models';

vi.mock('next-intl', () => ({ useTranslations: () => (key: string) => key }));
vi.mock('@/shared/utils/notification', () => ({
  notification: { success: vi.fn(), error: vi.fn(), info: vi.fn(), warning: vi.fn() },
}));

const achievedGoal: LearningGoal = {
  id: 5,
  skill: { id: 1, name: 'PostgreSQL', current_level: 3 },
  target_level: 3,
  target_level_label: 'Independent',
  target_date: '2026-12-31',
  status: 'achieved',
  achieved_on: '2026-10-01',
  note: null,
  created_at: null,
  updated_at: null,
};

const renderForm = (initialData: LearningGoal, onSuccess = vi.fn()) => {
  server.use(
    http.get(apiUrl('/admin/audit-log/list'), () =>
      HttpResponse.json(
        envelope({ data: [], current_page: 1, last_page: 1, per_page: 15, total: 0 }),
      ),
    ),
  );
  const { Wrapper } = createQueryWrapper();
  render(
    <Wrapper>
      <LearningGoalForm initialData={initialData} onSuccess={onSuccess} onCancel={vi.fn()} />
    </Wrapper>,
  );
  return onSuccess;
};

describe('LearningGoalForm', () => {
  it('sends only date and note fields for an achieved goal, without a status', async () => {
    let body: Record<string, unknown> | undefined;
    server.use(
      http.put(apiUrl('/admin/learning-goal/update/5'), async ({ request }) => {
        body = (await request.json()) as Record<string, unknown>;
        return HttpResponse.json(envelope(5));
      }),
    );
    const onSuccess = renderForm(achievedGoal);

    expect(screen.getByText('achievedHint')).toBeTruthy();
    expect(screen.queryByLabelText('status')).toBeNull();
    fireEvent.change(screen.getByLabelText('note'), { target: { value: 'Done early' } });
    fireEvent.click(screen.getByRole('button', { name: 'update' }));

    await waitFor(() => expect(onSuccess).toHaveBeenCalled());
    expect(body).toEqual({ id: 5, target_level: 3, target_date: '2026-12-31', note: 'Done early' });
  });

  it('shows the API rule on the target level field', async () => {
    server.use(
      http.put(apiUrl('/admin/learning-goal/update/5'), () =>
        HttpResponse.json(
          errorEnvelope(422, {
            target_level: ["Target level must be above the skill's current level"],
          }),
          { status: 422 },
        ),
      ),
    );
    renderForm({ ...achievedGoal, status: 'open', achieved_on: null });

    fireEvent.click(screen.getByRole('button', { name: 'update' }));

    expect(
      await screen.findByText("Target level must be above the skill's current level"),
    ).toBeTruthy();
  });
});
