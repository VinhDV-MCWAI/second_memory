import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { NextIntlClientProvider } from 'next-intl';
import messages from '@/../messages/en.json';
import { FilterPanel, type FilterField } from './filter-panel';

const fields: FilterField[] = [
  { key: 'title', label: 'Title', type: 'text' },
  { key: 'level', label: 'Level', type: 'select', options: [{ value: 1, label: 'One' }] },
  { key: 'is_public', label: 'Public', type: 'boolean' },
  {
    key: 'tags',
    label: 'Tags',
    type: 'custom',
    render: (value, onChange) => (
      <button type="button" onClick={() => onChange(Array.isArray(value) ? [] : [1, 2])}>
        Toggle tags
      </button>
    ),
  },
];

// Real messages, and a formatting error fails the test instead of printing the key
const renderPanel = (onFilterChange = vi.fn()) => {
  render(
    <NextIntlClientProvider
      locale="en"
      messages={messages}
      onError={(error) => {
        throw error;
      }}
    >
      <FilterPanel filters={{}} onFilterChange={onFilterChange} onReset={vi.fn()} fields={fields} />
    </NextIntlClientProvider>,
  );
  fireEvent.click(screen.getByRole('button', { name: 'Show' }));
  return onFilterChange;
};

describe('FilterPanel', () => {
  it('fills the field name into the placeholders', () => {
    renderPanel();

    expect(screen.getByPlaceholderText('Search title...')).toBeTruthy();
    expect(screen.getByText('Select level')).toBeTruthy();
  });

  it('applies a typed text filter', () => {
    const onFilterChange = renderPanel();

    fireEvent.change(screen.getByPlaceholderText('Search title...'), {
      target: { value: 'react' },
    });
    fireEvent.click(screen.getByRole('button', { name: 'Apply Filters' }));

    expect(onFilterChange).toHaveBeenCalledWith({ title: 'react' });
  });

  it('applies a custom field, and an emptied list clears it', () => {
    const onFilterChange = renderPanel();
    const apply = () => fireEvent.click(screen.getByRole('button', { name: 'Apply Filters' }));

    fireEvent.click(screen.getByRole('button', { name: 'Toggle tags' }));
    apply();
    expect(onFilterChange).toHaveBeenLastCalledWith({ tags: [1, 2] });

    fireEvent.click(screen.getByRole('button', { name: 'Toggle tags' }));
    apply();
    expect(onFilterChange).toHaveBeenLastCalledWith({ tags: undefined });
  });
});
