import { describe, expect, it } from 'vitest';
import { formatDateForBackend, formatDateForInput, formatTimestamp } from './date-formatter';
import { slugify } from './string-utils';
import { isArray, isObject, isString } from './type-guards';

describe('date formatters', () => {
  it('formats ISO strings and dates for inputs and the backend (d/m/Y)', () => {
    expect(formatDateForInput('2026-03-05T10:00:00')).toBe('2026-03-05');
    expect(formatDateForBackend('2026-03-05T10:00:00')).toBe('05/03/2026');
    expect(formatDateForBackend(new Date(2026, 0, 31))).toBe('31/01/2026');
  });

  it('returns an empty string for missing or invalid dates', () => {
    for (const value of [null, undefined, '', 'not a date']) {
      expect(formatDateForInput(value)).toBe('');
      expect(formatDateForBackend(value)).toBe('');
      expect(formatTimestamp(value)).toBe('');
    }
  });

  it('formats timestamps relative to now', () => {
    expect(formatTimestamp(new Date(Date.now() - 5 * 60 * 1000))).toBe('5 minutes ago');
  });
});

describe('slugify', () => {
  it('lowercases, hyphenates and strips symbols', () => {
    expect(slugify('  Hello World!  ')).toBe('hello-world');
    expect(slugify('a  --  b')).toBe('a-b');
  });
});

describe('type guards', () => {
  it('narrow primitives, arrays and plain objects', () => {
    expect(isString('x')).toBe(true);
    expect(isString(1)).toBe(false);
    expect(isArray([1])).toBe(true);
    expect(isArray('[]')).toBe(false);
    expect(isObject({ a: 1 })).toBe(true);
    expect(isObject(null)).toBe(false);
    expect(isObject([])).toBe(false);
  });
});
