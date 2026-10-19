import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { ArticleForm } from './article-form';
import { apiUrl, envelope, errorEnvelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';
import type { Article } from '@/shared/types/models';

vi.mock('next-intl', () => ({ useTranslations: () => (key: string) => key }));
vi.mock('@/shared/utils/notification', () => ({
  notification: { success: vi.fn(), error: vi.fn(), info: vi.fn(), warning: vi.fn() },
}));

const article: Article = {
  id: 7,
  content_category_id: 3,
  category_name: 'Thiết kế',
  category_slug: 'thiet-ke',
  title: 'Trình bày phương án',
  slug: 'trinh-bay-phuong-an',
  summary: null,
  body: '## Vấn đề',
  sort_order: 10,
  is_public: true,
  updated_at: null,
};

const page = <T,>(data: T[]) =>
  envelope({ data, current_page: 1, last_page: 1, per_page: 100, total: data.length });

const renderForm = (onSuccess = vi.fn()) => {
  server.use(
    http.get(apiUrl('/admin/audit-log/list'), () => HttpResponse.json(page([]))),
    http.get(apiUrl('/admin/content-category/list'), () =>
      HttpResponse.json(page([{ id: 3, name: 'Thiết kế' }])),
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
  it('sends the Markdown body and links to the public page', async () => {
    let body: Record<string, unknown> | undefined;
    server.use(
      http.put(apiUrl('/admin/article/update/7'), async ({ request }) => {
        body = (await request.json()) as Record<string, unknown>;
        return HttpResponse.json(envelope(7));
      }),
    );
    const onSuccess = renderForm();

    expect(screen.getByRole('link', { name: 'viewOnSite' }).getAttribute('href')).toBe(
      '/guides/thiet-ke/trinh-bay-phuong-an',
    );
    fireEvent.change(screen.getByLabelText(/^body/), { target: { value: '## Vấn đề\n\n- A' } });
    fireEvent.click(screen.getByRole('button', { name: 'save' }));

    await waitFor(() => expect(onSuccess).toHaveBeenCalled());
    expect(body).toMatchObject({
      content_category_id: 3,
      body: '## Vấn đề\n\n- A',
      is_public: true,
    });
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
