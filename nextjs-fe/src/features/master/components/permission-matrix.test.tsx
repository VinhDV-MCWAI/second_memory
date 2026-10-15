import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { PermissionMatrix } from './permission-matrix';
import type { PermissionGroup } from '@/shared/types/models';

vi.mock('next-intl', () => {
  const t = Object.assign((key: string) => key, { has: () => false });
  return { useTranslations: () => t };
});

const groups: PermissionGroup[] = [
  {
    feature: 'skill',
    permissions: [
      { name: 'skill.delete', action: 'delete', method: 'POST' },
      { name: 'skill.list', action: 'list', method: 'GET' },
    ],
  },
  {
    feature: 'evidence',
    permissions: [
      { name: 'evidence.import', action: 'import', method: 'POST' },
      { name: 'evidence.list', action: 'list', method: 'GET' },
    ],
  },
];

describe('PermissionMatrix', () => {
  it('toggles one permission, and a whole feature from its row', () => {
    const onChange = vi.fn();
    render(<PermissionMatrix groups={groups} value={['skill.list']} onChange={onChange} />);

    fireEvent.click(screen.getByRole('checkbox', { name: 'evidence.import' }));
    expect(onChange).toHaveBeenLastCalledWith(['evidence.import', 'skill.list']);

    // Row toggle of "skill": skill.delete was missing, so the whole row is selected
    fireEvent.click(screen.getAllByRole('checkbox', { name: 'wholeFeature' })[0]);
    expect(onChange).toHaveBeenLastCalledWith(['skill.delete', 'skill.list']);
  });

  it('puts actions outside the four CRUD columns in "other" and can be read-only', () => {
    render(<PermissionMatrix groups={groups} value={[]} onChange={vi.fn()} disabled />);

    screen.getByText('import');
    expect(screen.getByRole('checkbox', { name: 'evidence.list' }).hasAttribute('disabled')).toBe(
      true,
    );
  });
});
