import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, within } from '@testing-library/react';
import { CategoryTreeSelect } from './category-tree-select';

vi.mock('next-intl', () => ({
  useTranslations: () => (key: string, params?: Record<string, number>) =>
    params ? `${key}:${params.count}` : key,
}));

const categories = [
  { id: 1, parent_id: null, name: 'Kỹ năng' },
  { id: 2, parent_id: 1, name: 'Laravel' },
  { id: 3, parent_id: 2, name: 'Hàng đợi' },
  { id: 4, parent_id: null, name: 'Quy trình' },
];

const open = () => fireEvent.click(screen.getByRole('combobox'));
const search = (text: string) =>
  fireEvent.change(screen.getByRole('textbox', { name: 'searchCategories' }), {
    target: { value: text },
  });

describe('CategoryTreeSelect', () => {
  it('searches the whole path without accents, then selects and clears what it shows', () => {
    const onChange = vi.fn();
    const { rerender } = render(
      <CategoryTreeSelect
        id="c"
        mode="multiple"
        categories={categories}
        value={[4]}
        onChange={onChange}
      />,
    );

    open();
    search('ky nang');
    // A match shows where it sits; the other group is not shown
    expect(screen.getByText('Kỹ năng › Laravel › Hàng đợi')).toBeTruthy();
    expect(within(screen.getByRole('listbox')).queryByText('Quy trình')).toBeNull();

    fireEvent.click(screen.getByRole('button', { name: 'selectShown' }));
    expect(onChange).toHaveBeenLastCalledWith([4, 1, 2, 3]);

    rerender(
      <CategoryTreeSelect
        id="c"
        mode="multiple"
        categories={categories}
        value={[4, 1, 2, 3]}
        onChange={onChange}
      />,
    );
    fireEvent.click(screen.getByRole('button', { name: 'clearShown' }));
    // What the search hides stays selected
    expect(onChange).toHaveBeenLastCalledWith([4]);
  });

  it('picks one category, or none, and skips the disabled ones', () => {
    const onChange = vi.fn();
    render(
      <CategoryTreeSelect
        id="c"
        mode="single"
        noneLabel="none"
        categories={categories}
        disabledIds={new Set([2, 3])}
        value={3}
        onChange={onChange}
      />,
    );

    expect(screen.getByRole('combobox').textContent).toContain('Kỹ năng › Laravel › Hàng đợi');
    open();
    expect(screen.getByRole('option', { name: 'Laravel' })).toHaveProperty('disabled', true);
    fireEvent.click(screen.getByRole('option', { name: 'Quy trình' }));
    expect(onChange).toHaveBeenLastCalledWith(4);

    open();
    fireEvent.click(screen.getByRole('option', { name: 'none' }));
    expect(onChange).toHaveBeenLastCalledWith(null);
  });
});
