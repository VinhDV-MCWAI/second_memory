import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { SkillLevelTimeline } from './skill-level-timeline';
import { apiUrl, envelope, errorEnvelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';

vi.mock('next-intl', () => {
  const t = (key: string, params?: Record<string, string>) =>
    params ? `${key}:${JSON.stringify(params)}` : key;
  return { useTranslations: () => t };
});
vi.mock('@/shared/utils/notification', () => ({
  notification: { success: vi.fn(), error: vi.fn(), info: vi.fn(), warning: vi.fn() },
}));

const page = (data: unknown[]) =>
  envelope({ data, current_page: 1, last_page: 1, per_page: 100, total: data.length });

const entry = {
  id: 2,
  skill_id: 7,
  level: 3,
  level_label: 'Independent',
  reason: 'Shipped the audit log',
  changed_on: '2026-10-07',
  recorded_by_user_name: 'root',
  created_at: '2026-10-07T10:00:00+00:00',
};

const renderTimeline = () => {
  const { Wrapper } = createQueryWrapper();
  render(
    <Wrapper>
      <SkillLevelTimeline skillId={7} />
    </Wrapper>,
  );
};

describe('SkillLevelTimeline', () => {
  it('lists the history of one skill', async () => {
    let query: URLSearchParams | undefined;
    server.use(
      http.get(apiUrl('/admin/skill-level/list'), ({ request }) => {
        query = new URL(request.url).searchParams;
        return HttpResponse.json(page([entry]));
      }),
    );

    renderTimeline();

    expect(await screen.findByText('Shipped the audit log')).toBeTruthy();
    expect(query?.get('skill_id')).toBe('7');
    expect(screen.getByText('2026-10-07')).toBeTruthy();
    expect(screen.getByText('by:{"name":"root"}')).toBeTruthy();
  });

  it('records a change for this skill and leaves an empty date to the server', async () => {
    let body: Record<string, unknown> | undefined;
    server.use(
      http.get(apiUrl('/admin/skill-level/list'), () => HttpResponse.json(page([]))),
      http.post(apiUrl('/admin/skill-level/store'), async ({ request }) => {
        body = (await request.json()) as Record<string, unknown>;
        return HttpResponse.json(envelope(3));
      }),
    );

    renderTimeline();
    expect(await screen.findByText('empty')).toBeTruthy();
    fireEvent.change(screen.getByLabelText('reason'), { target: { value: 'Paired on k6' } });
    fireEvent.click(screen.getByRole('button', { name: 'record' }));

    await waitFor(() => expect(body).toEqual({ level: 1, reason: 'Paired on k6', skill_id: 7 }));
  });

  it('shows the server validation message on its field', async () => {
    server.use(
      http.get(apiUrl('/admin/skill-level/list'), () => HttpResponse.json(page([]))),
      http.post(apiUrl('/admin/skill-level/store'), () =>
        HttpResponse.json(errorEnvelope(422, { reason: ['Reason is too long'] }), { status: 422 }),
      ),
    );

    renderTimeline();
    fireEvent.click(await screen.findByRole('button', { name: 'record' }));

    expect(await screen.findByText('Reason is too long')).toBeTruthy();
  });

  it('refuses a date in the future before calling the API', async () => {
    server.use(http.get(apiUrl('/admin/skill-level/list'), () => HttpResponse.json(page([]))));

    renderTimeline();
    fireEvent.change(await screen.findByLabelText('changedOn'), {
      target: { value: '2999-01-01' },
    });
    fireEvent.click(screen.getByRole('button', { name: 'record' }));

    expect(await screen.findByText('changedOn.future')).toBeTruthy();
  });
});
