import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { SearchResults } from './search-results';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';

vi.mock('next-intl', () => {
  const t = (key: string, params?: Record<string, string | number>) =>
    params ? `${key}:${JSON.stringify(params)}` : key;
  return { useTranslations: () => t };
});

const renderResults = (q: string) => {
  const { Wrapper } = createQueryWrapper();
  render(
    <Wrapper>
      <SearchResults q={q} />
    </Wrapper>,
  );
};

describe('SearchResults', () => {
  it('sends the trimmed text and says when the match is fuzzy', async () => {
    let query: string | null = null;
    server.use(
      http.get(apiUrl('/admin/search'), ({ request }) => {
        query = new URL(request.url).searchParams.get('q');
        return HttpResponse.json(
          envelope({
            match: 'fuzzy',
            articles: [],
            categories: [],
            files: [
              {
                id: 3,
                is_file: true,
                name: 'roadmap.pdf',
                path: '/roadmap.pdf',
                size: 10,
                thumbnail_url: null,
              },
            ],
          }),
        );
      }),
    );

    renderResults(' roadmp ');

    expect(await screen.findByText('roadmap.pdf')).toBeTruthy();
    expect(query).toBe('roadmp');
    expect(screen.getByText('fuzzy')).toBeTruthy();
  });

  it('links files and folders to their folder in Files', async () => {
    server.use(
      http.get(apiUrl('/admin/search'), () =>
        HttpResponse.json(
          envelope({
            match: 'exact',
            articles: [],
            categories: [],
            files: [
              {
                id: 7,
                is_file: false,
                name: 'Hợp đồng',
                path: '/Docs/Hợp đồng',
                size: null,
                thumbnail_url: null,
              },
              {
                id: 8,
                is_file: true,
                name: 'hop-dong.jpg',
                path: '/Docs/Hợp đồng/hop-dong.jpg',
                size: 2048,
                thumbnail_url: 'http://storage.test/thumb.jpg',
              },
            ],
          }),
        ),
      ),
    );

    renderResults('hop dong');

    const folder = await screen.findByRole('link', { name: /^Hợp đồng/ });
    expect(folder.getAttribute('href')).toBe(
      `/admin/files?folder=${encodeURIComponent('/Docs/Hợp đồng')}`,
    );
    const file = screen.getByRole('link', { name: /hop-dong\.jpg/ });
    expect(file.getAttribute('href')).toBe(
      `/admin/files?folder=${encodeURIComponent('/Docs/Hợp đồng')}`,
    );
    expect(file.querySelector('img')?.getAttribute('src')).toBe('http://storage.test/thumb.jpg');
    expect(screen.getByText('/Docs/Hợp đồng · 2.0 KB')).toBeTruthy();
    expect(screen.queryByText('fuzzy')).toBeNull();
  });

  it('links public articles and categories to the public site and marks private ones', async () => {
    server.use(
      http.get(apiUrl('/admin/search'), () =>
        HttpResponse.json(
          envelope({
            match: 'exact',
            articles: [
              { id: 1, title: 'Hàng đợi', slug: 'hang-doi', snippet: 'Việc nền.', is_public: true },
              { id: 2, title: 'Nháp', slug: 'nhap', snippet: null, is_public: false },
            ],
            categories: [
              { id: 3, name: 'Laravel', slug: 'laravel', group: 'Kỹ năng', is_public: true },
            ],
            files: [],
          }),
        ),
      ),
    );

    renderResults('hang doi');

    const article = await screen.findByRole('link', { name: 'Hàng đợi' });
    expect(article.getAttribute('href')).toBe('/articles/hang-doi');
    expect(article.getAttribute('target')).toBe('_blank');
    expect(screen.getByText('Việc nền.')).toBeTruthy();
    // A private article is not linked to the public site
    expect(screen.queryByRole('link', { name: 'Nháp' })).toBeNull();
    expect(screen.getByText('private')).toBeTruthy();
    expect(screen.getByRole('link', { name: 'Laravel' }).getAttribute('href')).toBe(
      '/categories/laravel',
    );
    expect(screen.getByText('Kỹ năng')).toBeTruthy();
    expect(screen.queryByText('files')).toBeNull();
  });

  it('shows an empty state when nothing matches', async () => {
    server.use(
      http.get(apiUrl('/admin/search'), () =>
        HttpResponse.json(envelope({ match: 'fuzzy', articles: [], categories: [], files: [] })),
      ),
    );

    renderResults('zzqxj');

    expect(await screen.findByText('empty:{"q":"zzqxj"}')).toBeTruthy();
  });

  it('does not call the API for one character', () => {
    renderResults('a');

    expect(screen.getByText('hint:{"min":2}')).toBeTruthy();
  });
});
