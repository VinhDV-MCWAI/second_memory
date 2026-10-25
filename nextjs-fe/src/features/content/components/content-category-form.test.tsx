import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { ContentCategoryForm } from './content-category-form';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';
import type { ContentCategory } from '@/shared/types/models';

vi.mock('next-intl', () => ({ useTranslations: () => (key: string) => key }));
vi.mock('@/shared/hooks/use-permissions', () => ({ usePermissions: () => ({ can: () => true }) }));
vi.mock('@/shared/utils/notification', () => ({
  notification: { success: vi.fn(), error: vi.fn(), info: vi.fn(), warning: vi.fn() },
}));

const category: ContentCategory = {
  id: 5,
  parent_id: 1,
  parent_name: 'Kỹ năng',
  name: 'Laravel',
  slug: 'laravel',
  description: '**API**',
  sort_order: 1,
  is_public: true,
  article_count: 0,
  children_count: 0,
  depth: 1,
  attachments: [
    {
      id: 9,
      title: 'Docs',
      url: 'https://laravel.com/docs',
      media_id: null,
      note: null,
      file: null,
    },
  ],
  updated_at: null,
};

const page = <T,>(data: T[]) =>
  envelope({ data, current_page: 1, last_page: 1, per_page: 100, total: data.length });

const renderForm = (initialData: ContentCategory | null, onSuccess = vi.fn()) => {
  server.use(
    http.get(apiUrl('/admin/audit-log/list'), () => HttpResponse.json(page([]))),
    http.get(apiUrl('/admin/content-category/list'), () =>
      HttpResponse.json(page([{ ...category, id: 1, parent_id: null, name: 'Kỹ năng' }])),
    ),
  );
  const { Wrapper } = createQueryWrapper();
  render(
    <Wrapper>
      <ContentCategoryForm
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

describe('ContentCategoryForm', () => {
  it('keeps attachments by id and sends links without blanks', async () => {
    let body: Record<string, unknown> | undefined;
    server.use(
      http.put(apiUrl('/admin/content-category/update/5'), async ({ request }) => {
        body = (await request.json()) as Record<string, unknown>;
        return HttpResponse.json(envelope(5));
      }),
    );
    const onSuccess = renderForm(category);

    expect(screen.getByRole('link', { name: 'viewOnSite' }).getAttribute('href')).toBe(
      '/categories/laravel',
    );
    fireEvent.click(screen.getByRole('button', { name: 'addLink' }));
    fireEvent.change(screen.getAllByLabelText('attachmentTitle')[1], {
      target: { value: 'Profile' },
    });
    fireEvent.change(screen.getAllByLabelText('attachmentUrl')[1], {
      target: { value: 'https://example.com' },
    });
    fireEvent.click(screen.getAllByRole('button', { name: 'moveUp' })[1]);
    fireEvent.click(screen.getByRole('button', { name: 'save' }));

    await waitFor(() => expect(onSuccess).toHaveBeenCalled());
    expect(body).toMatchObject({
      parent_id: 1,
      attachments: [
        { title: 'Profile', url: 'https://example.com', media_id: null, note: null },
        { id: 9, title: 'Docs', url: 'https://laravel.com/docs', media_id: null, note: null },
      ],
    });
  });

  it('wants a link or a file on each attachment', async () => {
    const onSuccess = renderForm(null);

    fireEvent.change(screen.getByLabelText(/^name/), { target: { value: 'AWS' } });
    fireEvent.click(screen.getByRole('button', { name: 'addLink' }));
    fireEvent.change(screen.getByLabelText('attachmentTitle'), { target: { value: 'Empty' } });
    fireEvent.click(screen.getByRole('button', { name: 'save' }));

    expect(await screen.findByText('url.linkOrFile')).toBeTruthy();
    expect(onSuccess).not.toHaveBeenCalled();
  });
});
