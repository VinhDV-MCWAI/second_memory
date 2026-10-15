import { describe, expect, it } from 'vitest';
import type { MediaItem } from '@/shared/types/models';
import { awaitingMediaInfo, MEDIA_INFO_WAIT_MS } from './media-info';

const NOW = Date.parse('2026-10-15T10:00:00Z');
const file = (preview: string | null, changes: Partial<MediaItem> = {}): MediaItem => ({
  id: 1,
  is_file: true,
  name: 'a',
  path: '/a',
  extension: null,
  mime_type: null,
  size: 10,
  width: null,
  height: null,
  duration: null,
  thumbnail_url: null,
  upload_status: 2,
  preview,
  created_at: null,
  updated_at: new Date(NOW - 1_000).toISOString(),
  ...changes,
});

describe('awaitingMediaInfo', () => {
  it('waits for the thumbnail of an image and the duration of audio', () => {
    expect(awaitingMediaInfo([file('image')], NOW)).toBe(true);
    expect(awaitingMediaInfo([file('image', { thumbnail_url: 'http://t' })], NOW)).toBe(false);
    expect(awaitingMediaInfo([file('audio')], NOW)).toBe(true);
    expect(awaitingMediaInfo([file('audio', { duration: 7 })], NOW)).toBe(false);
    expect(awaitingMediaInfo([file('video', { thumbnail_url: 'http://t' })], NOW)).toBe(true);
  });

  it('does not wait for other files, unfinished uploads or old files', () => {
    expect(awaitingMediaInfo([file('pdf'), file(null)], NOW)).toBe(false);
    expect(awaitingMediaInfo([file('image', { upload_status: 0 })], NOW)).toBe(false);
    const old = new Date(NOW - MEDIA_INFO_WAIT_MS - 1).toISOString();
    expect(awaitingMediaInfo([file('image', { updated_at: old })], NOW)).toBe(false);
  });
});
