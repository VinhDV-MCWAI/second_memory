import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { EvidenceForm } from './evidence-form';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';
import type { Evidence } from '@/shared/types/models';

vi.mock('next-intl', () => ({ useTranslations: () => (key: string) => key }));
vi.mock('@/shared/hooks/use-permissions', () => ({
  usePermissions: () => ({ can: () => true }),
}));
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
  files: [
    {
      id: 3,
      is_file: true,
      name: 'slides.pdf',
      path: '/slides.pdf',
      extension: 'pdf',
      mime_type: 'application/pdf',
      size: 1024,
      width: null,
      height: null,
      duration: null,
      thumbnail_url: null,
      upload_status: 2,
      preview: 'pdf',
      created_at: null,
      updated_at: null,
      available: true,
      locked: false,
    },
  ],
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
      <EvidenceForm
        initialData={initialData}
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

describe('EvidenceForm', () => {
  it('rejects a link that is not http(s) before calling the API', async () => {
    renderForm(null);

    fireEvent.change(screen.getByLabelText(/title/), { target: { value: 'XSS' } });
    fireEvent.change(screen.getByLabelText(/url/), { target: { value: 'javascript:alert(1)' } });
    fireEvent.click(screen.getByRole('button', { name: 'save' }));

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
    fireEvent.click(screen.getByRole('button', { name: 'save' }));

    await waitFor(() => expect(onSuccess).toHaveBeenCalled());
    expect(body).toMatchObject({
      title: 'Vault note',
      url: imported.url,
      skill_ids: [1],
      media_ids: [3],
    });
    // Attachments are not vault-owned: they stay editable
    expect(screen.getByRole('button', { name: 'remove' })).toBeTruthy();
  });

  it('needs a link or a file, and sends an empty link as null when files are attached', async () => {
    let body: Record<string, unknown> | undefined;
    server.use(
      http.put(apiUrl('/admin/evidence/update/9'), async ({ request }) => {
        body = (await request.json()) as Record<string, unknown>;
        return HttpResponse.json(envelope(9));
      }),
    );
    const manual: Evidence = { ...imported, source: 'manual' };
    const onSuccess = renderForm(manual);

    fireEvent.change(screen.getByLabelText(/url/), { target: { value: '' } });
    fireEvent.click(screen.getByRole('button', { name: 'save' }));
    await waitFor(() => expect(onSuccess).toHaveBeenCalled());
    expect(body).toMatchObject({ url: null, media_ids: [3] });

    fireEvent.click(screen.getByRole('button', { name: 'remove' }));
    fireEvent.click(screen.getByRole('button', { name: 'save' }));
    expect(await screen.findByText('url.linkOrFile')).toBeTruthy();
  });
});
