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
  it('shows non-empty groups and says when the match is fuzzy', async () => {
    let query: string | null = null;
    server.use(
      http.get(apiUrl('/admin/search'), ({ request }) => {
        query = new URL(request.url).searchParams.get('q');
        return HttpResponse.json(
          envelope({
            match: 'fuzzy',
            skills: [{ id: 1, title: 'PostgreSQL', snippet: 'Tối ưu truy vấn' }],
            goals: [],
            evidence: [{ id: 4, title: 'PostgreSQL partitioning', snippet: null }],
          }),
        );
      }),
    );

    renderResults(' postgersql ');

    expect(await screen.findByText('PostgreSQL partitioning')).toBeTruthy();
    expect(query).toBe('postgersql');
    expect(screen.getByText('fuzzy')).toBeTruthy();
    expect(screen.getByText('Tối ưu truy vấn')).toBeTruthy();
    expect(screen.queryByText('goals')).toBeNull();
  });

  it('shows an empty state when nothing matches', async () => {
    server.use(
      http.get(apiUrl('/admin/search'), () =>
        HttpResponse.json(envelope({ match: 'fuzzy', skills: [], goals: [], evidence: [] })),
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
