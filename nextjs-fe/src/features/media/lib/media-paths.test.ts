import { describe, expect, it } from 'vitest';
import {
  crumbsOf,
  formatBytes,
  formatDuration,
  formatFileSize,
  joinPath,
  parentOf,
  ROOT,
} from './media-paths';

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
    expect(formatFileSize({ size: 2048, width: 1920, height: 1080 })).toBe('2.0 KB · 1920 × 1080');
    expect(formatFileSize({ size: 2048, width: null, height: null })).toBe('2.0 KB');
    expect(formatFileSize({ size: 2048, width: 1280, height: 720, duration: 75 })).toBe(
      '2.0 KB · 1280 × 720 · 1:15',
    );
    expect(formatDuration(0)).toBe('0:00');
    expect(formatDuration(3725)).toBe('1:02:05');
    expect(formatBytes(null)).toBe('');
  });
});
