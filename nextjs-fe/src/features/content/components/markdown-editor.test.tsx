import { useState } from 'react';
import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { MarkdownEditor } from './markdown-editor';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';

vi.mock('next-intl', () => ({ useTranslations: () => (key: string) => key }));
vi.mock('@/shared/hooks/use-permissions', () => ({ usePermissions: () => ({ can: () => true }) }));

const page = <T,>(data: T[]) =>
  envelope({ data, current_page: 1, last_page: 1, per_page: 20, total: data.length });

function Harness({ initial }: { initial: string }) {
  const [value, setValue] = useState(initial);
  return (
    <>
      <MarkdownEditor id="body" value={value} onChange={setValue} articleId={1} />
      <output data-testid="value">{value}</output>
    </>
  );
}

const renderEditor = (initial: string) => {
  const { Wrapper } = createQueryWrapper();
  render(
    <Wrapper>
      <Harness initial={initial} />
    </Wrapper>,
  );
};

describe('MarkdownEditor', () => {
  it('embeds a picked article on lines of its own and links inline', async () => {
    server.use(
      http.get(apiUrl('/admin/article/list'), () =>
        HttpResponse.json(
          page([
            { id: 1, title: 'This one', slug: 'this-one' },
            { id: 2, title: 'Redis', slug: 'redis' },
          ]),
        ),
      ),
    );
    renderEditor('Intro');

    fireEvent.click(screen.getByRole('button', { name: 'insertArticle' }));
    // The article being edited is not offered
    expect(await screen.findByText('Redis')).toBeTruthy();
    expect(screen.queryByText('This one')).toBeNull();
    fireEvent.click(screen.getByRole('button', { name: 'embed' }));

    expect(screen.getByTestId('value').textContent).toBe('Intro\n\n![[redis]]\n\n');

    // At the cursor, inline for a link
    const textarea = screen.getByRole('textbox') as HTMLTextAreaElement;
    textarea.setSelectionRange(5, 5);
    fireEvent.select(textarea);
    fireEvent.click(screen.getByRole('button', { name: 'insertArticle' }));
    fireEvent.click((await screen.findAllByRole('button', { name: 'link' }))[0]);
    expect(screen.getByTestId('value').textContent).toBe('Intro[[redis]]\n\n![[redis]]\n\n');
  });

  it('shows the server rendering in the Preview tab', async () => {
    let sent: unknown;
    server.use(
      http.post(apiUrl('/admin/article/preview'), async ({ request }) => {
        sent = await request.json();
        return HttpResponse.json(envelope({ html: '<h2 id="a">Rendered</h2>', toc: [] }));
      }),
    );
    renderEditor('## A');

    fireEvent.mouseDown(screen.getByRole('tab', { name: 'preview' }));

    await waitFor(() => expect(screen.getByRole('heading', { name: 'Rendered' })).toBeTruthy());
    expect(sent).toEqual({ body: '## A' });
  });
});
