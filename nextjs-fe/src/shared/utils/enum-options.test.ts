import { describe, expect, it } from 'vitest';
import { enumOptions } from './enum-options';
import { IsActiveLabels } from '@/shared/enums';

describe('enumOptions', () => {
  it('turns a label map into string-valued select options', () => {
    expect(enumOptions(IsActiveLabels)).toEqual([
      { value: '0', label: 'Inactive' },
      { value: '1', label: 'Active' },
    ]);
  });
});
