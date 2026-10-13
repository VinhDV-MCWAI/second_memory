import { describe, expect, it } from 'vitest';
import { crumbsOf, formatBytes, joinPath, parentOf, ROOT } from './media-paths';

describe('media paths', () => {
  it('joins and splits virtual paths', () => {
    expect(joinPath(ROOT, 'a.txt')).toBe('/a.txt');
    expect(joinPath('/Docs', 'a.txt')).toBe('/Docs/a.txt');
    expect(parentOf('/Docs/a.txt')).toBe('/Docs');
    expect(parentOf('/a.txt')).toBe(ROOT);
  });

  it('builds the breadcrumb of a folder', () => {
    expect(crumbsOf(ROOT)).toEqual([]);
    expect(crumbsOf('/A/B')).toEqual([
      { name: 'A', path: '/A' },
      { name: 'B', path: '/A/B' },
    ]);
  });

  it('formats sizes', () => {
    expect(formatBytes(512)).toBe('512 B');
    expect(formatBytes(1536)).toBe('1.5 KB');
    expect(formatBytes(3 * 1024 ** 3)).toBe('3.0 GB');
    expect(formatBytes(250 * 1024 ** 2)).toBe('250 MB');
    expect(formatBytes(null)).toBe('');
  });
});
