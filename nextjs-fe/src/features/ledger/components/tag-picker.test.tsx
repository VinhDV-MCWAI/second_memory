import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { TagPicker } from './tag-picker';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';

vi.mock('next-intl', () => ({ useTranslations: () => (key: string) => key }));

const tags = (data: { id: number; name: string }[]) =>
  HttpResponse.json(envelope({ data, current_page: 1, last_page: 1, per_page: 100, total: 2 }));

const renderPicker = (value: number[], onChange = vi.fn()) => {
  const { Wrapper } = createQueryWrapper();
  render(
    <Wrapper>
      <TagPicker value={value} onChange={onChange} />
    </Wrapper>,
  );
  return onChange;
};

describe('TagPicker', () => {
  it('adds and removes tag ids', async () => {
    server.use(
      http.get(apiUrl('/admin/tag/list'), () =>
        tags([
          { id: 1, name: 'php' },
          { id: 2, name: 'sql' },
        ]),
      ),
    );

    const onChange = renderPicker([1]);
    fireEvent.click(await screen.findByLabelText('sql'));
    expect(onChange).toHaveBeenLastCalledWith([1, 2]);

    fireEvent.click(screen.getByLabelText('php'));
    expect(onChange).toHaveBeenLastCalledWith([]);
  });

  it('says so when there are no tags', async () => {
    server.use(http.get(apiUrl('/admin/tag/list'), () => tags([])));

    renderPicker([]);

    expect(await screen.findByText('noTags')).toBeTruthy();
  });
});
