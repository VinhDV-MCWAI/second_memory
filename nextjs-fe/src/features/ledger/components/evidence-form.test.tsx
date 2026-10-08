import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { EvidenceForm } from './evidence-form';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';
import type { Evidence } from '@/shared/types/models';

vi.mock('next-intl', () => ({ useTranslations: () => (key: string) => key }));
vi.mock('@/shared/utils/notification', () => ({
  notification: { success: vi.fn(), error: vi.fn(), info: vi.fn(), warning: vi.fn() },
}));

const page = (data: unknown[]) =>
  HttpResponse.json(envelope({ data, current_page: 1, last_page: 1, per_page: 100, total: 1 }));

const imported: Evidence = {
  id: 9,
  type: 'note',
  title: 'Vault note',
  url: 'https://notes.example.com/a',
  occurred_on: '2026-09-01',
  summary: null,
  is_public: true,
  source: 'obsidian',
  unpublished_at: null,
  skills: [{ id: 1, name: 'Laravel' }],
  tags: [],
  created_at: null,
  updated_at: null,
};

const renderForm = (initialData: Evidence | null, onSuccess = vi.fn()) => {
  server.use(
    http.get(apiUrl('/admin/skill/list'), () => page([{ id: 1, name: 'Laravel' }])),
    http.get(apiUrl('/admin/tag/list'), () => page([])),
    http.get(apiUrl('/admin/audit-log/list'), () => page([])),
  );
  const { Wrapper } = createQueryWrapper();
  render(
    <Wrapper>
      <EvidenceForm initialData={initialData} onSuccess={onSuccess} onCancel={vi.fn()} />
    </Wrapper>,
  );
  return onSuccess;
};

describe('EvidenceForm', () => {
  it('rejects a link that is not http(s) before calling the API', async () => {
    renderForm(null);

    fireEvent.change(screen.getByLabelText(/title/), { target: { value: 'XSS' } });
    fireEvent.change(screen.getByLabelText(/url/), { target: { value: 'javascript:alert(1)' } });
    fireEvent.click(screen.getByRole('button', { name: 'create' }));

    expect(await screen.findByText('url.invalid')).toBeTruthy();
    expect(screen.getByText('skillIds.required')).toBeTruthy();
  });

  it('keeps the vault-owned fields of an imported row read-only and sends them unchanged', async () => {
    let body: Record<string, unknown> | undefined;
    server.use(
      http.put(apiUrl('/admin/evidence/update/9'), async ({ request }) => {
        body = (await request.json()) as Record<string, unknown>;
        return HttpResponse.json(envelope(9));
      }),
    );
    const onSuccess = renderForm(imported);

    expect(screen.getByText('importedHint')).toBeTruthy();
    expect((screen.getByLabelText(/title/) as HTMLInputElement).disabled).toBe(true);
    expect((screen.getByLabelText(/url/) as HTMLInputElement).disabled).toBe(true);
    fireEvent.click(screen.getByRole('button', { name: 'update' }));

    await waitFor(() => expect(onSuccess).toHaveBeenCalled());
    expect(body).toMatchObject({ title: 'Vault note', url: imported.url, skill_ids: [1] });
  });
});
