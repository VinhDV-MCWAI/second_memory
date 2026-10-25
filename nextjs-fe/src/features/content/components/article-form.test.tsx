import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { ArticleForm } from './article-form';
import { apiUrl, envelope, errorEnvelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';
import type { Article } from '@/shared/types/models';

vi.mock('next-intl', () => ({ useTranslations: () => (key: string) => key }));
vi.mock('@/shared/hooks/use-permissions', () => ({ usePermissions: () => ({ can: () => true }) }));
vi.mock('@/shared/utils/notification', () => ({
  notification: { success: vi.fn(), error: vi.fn(), info: vi.fn(), warning: vi.fn() },
}));

const article: Article = {
  id: 7,
  parent_id: null,
  parent_title: null,
  categories: [{ id: 3, name: 'Thiết kế', slug: 'thiet-ke' }],
  title: 'Trình bày phương án',
  slug: 'trinh-bay-phuong-an',
  summary: null,
  body: '## Vấn đề',
  sort_order: 1,
  is_public: true,
  children_count: 1,
  updated_at: null,
};

const chapter = { ...article, id: 8, parent_id: 7, title: 'Chương 1', slug: 'chuong-1' };

const page = <T,>(data: T[]) =>
  envelope({ data, current_page: 1, last_page: 1, per_page: 100, total: data.length });

const renderForm = (onSuccess = vi.fn()) => {
  server.use(
    http.get(apiUrl('/admin/audit-log/list'), () => HttpResponse.json(page([]))),
    http.get(apiUrl('/admin/content-category/list'), () =>
      HttpResponse.json(
        page([
          { id: 3, name: 'Thiết kế' },
          { id: 4, name: 'QA' },
        ]),
      ),
    ),
    // The parent select lists every article; the sub-pages panel only the children of #7
    http.get(apiUrl('/admin/article/list'), ({ request }) =>
      HttpResponse.json(
        page(
          new URL(request.url).searchParams.get('parent_id') === '7'
            ? [chapter]
            : [article, chapter],
        ),
      ),
    ),
  );
  const { Wrapper } = createQueryWrapper();
  render(
    <Wrapper>
      <ArticleForm
        initialData={article}
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

describe('ArticleForm', () => {
  it('sends the categories, the parent and the Markdown body and links to the public page', async () => {
    let body: Record<string, unknown> | undefined;
    server.use(
      http.put(apiUrl('/admin/article/update/7'), async ({ request }) => {
        body = (await request.json()) as Record<string, unknown>;
        return HttpResponse.json(envelope(7));
      }),
    );
    const onSuccess = renderForm();

    expect(screen.getByRole('link', { name: 'viewOnSite' }).getAttribute('href')).toBe(
      '/articles/trinh-bay-phuong-an',
    );
    // The categories picker opens as a tree with checkboxes
    fireEvent.click(screen.getByRole('combobox', { name: /categories/ }));
    fireEvent.click(await screen.findByLabelText('QA'));
    fireEvent.change(screen.getByLabelText(/^body/), { target: { value: '## Vấn đề\n\n- A' } });
    fireEvent.click(screen.getByRole('button', { name: 'save' }));

    await waitFor(() => expect(onSuccess).toHaveBeenCalled());
    expect(body).toMatchObject({
      category_ids: [3, 4],
      parent_id: null,
      body: '## Vấn đề\n\n- A',
      is_public: true,
    });
  });

  it('picks the parent from the book tree, never itself or one of its sub-pages', async () => {
    renderForm();

    fireEvent.click(screen.getByRole('combobox', { name: /parent/ }));
    const own = await screen.findByRole('option', { name: 'Trình bày phương án' });
    expect(own.hasAttribute('disabled')).toBe(true);
    expect(screen.getByRole('option', { name: 'Chương 1' }).hasAttribute('disabled')).toBe(true);
    expect(screen.getByRole('textbox', { name: 'searchArticles' })).toBeTruthy();
  });

  it('lists its sub-pages in book order', async () => {
    renderForm();

    // The sub-pages panel, not the hidden option of the parent select
    expect(await screen.findByText('Chương 1', { selector: 'span' })).toBeTruthy();
    expect(screen.getByText('subPages')).toBeTruthy();
  });

  it('shows the API error on the field', async () => {
    server.use(
      http.put(apiUrl('/admin/article/update/7'), () =>
        HttpResponse.json(errorEnvelope(422, { title: ['The Title has already been taken.'] }), {
          status: 422,
        }),
      ),
    );
    renderForm();

    fireEvent.click(screen.getByRole('button', { name: 'save' }));

    expect(await screen.findByText('The Title has already been taken.')).toBeTruthy();
  });
});
