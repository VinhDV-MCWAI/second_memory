import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { useState } from 'react';
import { apiUrl, envelope, server } from '@/test/server';
import { createQueryWrapper } from '@/test/query-wrapper';
import type { EvidenceFile } from '@/shared/types/models';
import { EvidenceFilesField } from './evidence-files-field';

vi.mock('next-intl', () => {
  const t = (key: string, values?: Record<string, unknown>) =>
    values?.name ? `${key} ${String(values.name)}` : key;
  return { useTranslations: () => t };
});
let granted: string[] = [];
vi.mock('@/shared/hooks/use-permissions', () => ({
  usePermissions: () => ({ can: (permission: string) => granted.includes(permission) }),
}));
vi.mock('@/shared/utils/notification', () => ({
  notification: { success: vi.fn(), error: vi.fn() },
}));

const file = (id: number, name: string, available = true): EvidenceFile => ({
  id,
  is_file: true,
  name,
  path: `/${name}`,
  extension: null,
  mime_type: null,
  size: 2048,
  width: null,
  height: null,
  duration: null,
  thumbnail_url: null,
  upload_status: 2,
  preview: null,
  created_at: null,
  updated_at: null,
  available,
});
const page = (rows: unknown[]) =>
  envelope({
    data: rows,
    links: {},
    meta: {
      current_page: 1,
      last_page: 1,
      per_page: 50,
      total: rows.length,
      from: 1,
      to: rows.length,
    },
  });

/** The field inside a parent that keeps its value, like react-hook-form does. */
function renderField(initialFiles: EvidenceFile[]) {
  const changes: number[][] = [];
  function Host() {
    const [ids, setIds] = useState(initialFiles.map((item) => item.id));
    return (
      <EvidenceFilesField
        value={ids}
        onChange={(next) => {
          changes.push(next);
          setIds(next);
        }}
        initialFiles={initialFiles}
      />
    );
  }
  const { Wrapper } = createQueryWrapper();
  render(<Host />, { wrapper: Wrapper });
  return changes;
}

describe('EvidenceFilesField', () => {
  beforeEach(() => {
    granted = ['media.list', 'media.download-url'];
  });

  it('marks a deleted file, reorders and removes attachments', () => {
    const changes = renderField([file(1, 'a.pdf'), file(2, 'b.zip', false)]);

    expect(screen.getByText('deleted')).toBeTruthy();
    // A deleted file cannot be downloaded
    expect(screen.getAllByRole('button', { name: 'download' })).toHaveLength(1);

    fireEvent.click(screen.getAllByRole('button', { name: 'moveUp' })[1]);
    expect(changes.at(-1)).toEqual([2, 1]);

    fireEvent.click(screen.getByRole('button', { name: 'remove b.zip' }));
    expect(changes.at(-1)).toEqual([1]);
    expect(screen.queryByText('b.zip')).toBeNull();
  });

  it('picks files from a folder of the file manager', async () => {
    server.use(
      http.get(apiUrl('/admin/media/list'), ({ request }) => {
        const folder = new URL(request.url).searchParams.get('folder');
        return HttpResponse.json(
          folder === '/Docs'
            ? page([{ ...file(5, 'cert.pdf'), path: '/Docs/cert.pdf' }])
            : page([{ ...file(4, 'Docs'), is_file: false, size: null }, file(1, 'a.pdf')]),
        );
      }),
    );
    const changes = renderField([file(1, 'a.pdf')]);

    fireEvent.click(screen.getByRole('button', { name: 'attach' }));
    // Already attached: ticked and locked
    const attached = await screen.findByRole('checkbox', { name: 'a.pdf' });
    expect(attached.getAttribute('data-state')).toBe('checked');
    expect(attached.hasAttribute('disabled')).toBe(true);

    fireEvent.click(screen.getByRole('button', { name: 'Docs' }));
    fireEvent.click(await screen.findByRole('checkbox', { name: 'cert.pdf' }));
    fireEvent.click(screen.getByRole('button', { name: 'attach' }));

    await waitFor(() => expect(changes.at(-1)).toEqual([1, 5]));
    expect(screen.getByText('cert.pdf')).toBeTruthy();
  });

  it('hides the attach button without access to Files', () => {
    granted = [];
    renderField([]);

    expect(screen.getByText('empty')).toBeTruthy();
    expect(screen.queryByRole('button', { name: 'attach' })).toBeNull();
  });
});
