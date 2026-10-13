import { afterEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { AppDialog, type AppDialogProps } from './app-dialog';

// A stable translator that returns the key, so assertions can use message keys.
vi.mock('next-intl', () => {
  const t = (key: string) => key;
  return { useTranslations: () => t };
});
let granted: string[] = [];
vi.mock('@/shared/hooks/use-permissions', () => ({
  usePermissions: () => ({ can: (permission: string) => granted.includes(permission) }),
}));

const renderDialog = (props: Partial<AppDialogProps> = {}) => {
  const onOpenChange = vi.fn();
  render(
    <AppDialog open onOpenChange={onOpenChange} title="Title" {...props}>
      <p>body</p>
    </AppDialog>,
  );
  return onOpenChange;
};

const footerButtons = () =>
  within(screen.getByRole('dialog'))
    .getAllByRole('button')
    .map((button) => button.textContent)
    .filter((name) => name !== 'close');

describe('AppDialog', () => {
  afterEach(() => {
    granted = [];
    vi.restoreAllMocks();
  });

  it('renders Cancel then the actions in order, with default look and no-op click', () => {
    renderDialog({
      actions: [
        { key: 'a', label: 'first' },
        { key: 'b', label: 'second', variant: 'destructive', size: 'sm' },
      ],
    });

    expect(footerButtons()).toEqual(['cancel', 'first', 'second']);
    const first = screen.getByRole('button', { name: 'first' });
    expect(first.getAttribute('type')).toBe('button');
    fireEvent.click(first); // no onClick declared: nothing happens, nothing throws
  });

  it('runs an action and locks the footer while it is pending', async () => {
    let finish = () => {};
    const onClick = vi.fn(() => new Promise<void>((resolve) => (finish = resolve)));
    renderDialog({ actions: [{ key: 'save', label: 'save', onClick }] });

    fireEvent.click(screen.getByRole('button', { name: 'save' }));
    expect(onClick).toHaveBeenCalledOnce();
    expect(screen.getByRole('button', { name: 'cancel' })).toHaveProperty('disabled', true);

    finish();
    await waitFor(() =>
      expect(screen.getByRole('button', { name: 'cancel' })).toHaveProperty('disabled', false),
    );
  });

  it('shows at most maxActions buttons', () => {
    vi.spyOn(console, 'error').mockImplementation(() => {});
    renderDialog({
      maxActions: 2,
      actions: ['a', 'b', 'c'].map((key) => ({ key, label: key })),
    });

    expect(footerButtons()).toEqual(['cancel', 'a', 'b']);
    expect(console.error).toHaveBeenCalled();
  });

  it('hides actions without their permission', () => {
    granted = ['role.update'];
    renderDialog({
      actions: [
        { key: 'delete', label: 'delete', permission: 'role.delete' },
        { key: 'save', label: 'save', permission: 'role.update' },
      ],
    });

    expect(footerButtons()).toEqual(['cancel', 'save']);
  });

  it('closes straight away when nothing changed', () => {
    const onOpenChange = renderDialog();

    fireEvent.click(screen.getByRole('button', { name: 'cancel' }));

    expect(onOpenChange).toHaveBeenCalledWith(false);
  });

  it('asks before discarding unsaved changes', async () => {
    const onOpenChange = renderDialog({ dirty: true });

    fireEvent.click(screen.getByRole('button', { name: 'cancel' }));
    const confirm = await screen.findByRole('alertdialog');
    expect(onOpenChange).not.toHaveBeenCalled();

    fireEvent.click(within(confirm).getByRole('button', { name: 'discard' }));

    expect(onOpenChange).toHaveBeenCalledWith(false);
  });

  it('keeps the dialog open when the admin chooses to keep editing', async () => {
    const onOpenChange = renderDialog({ dirty: true });

    fireEvent.keyDown(screen.getByRole('dialog'), { key: 'Escape' });
    const confirm = await screen.findByRole('alertdialog');
    fireEvent.click(within(confirm).getByRole('button', { name: 'keepEditing' }));

    await waitFor(() => expect(screen.queryByRole('alertdialog')).toBeNull());
    expect(onOpenChange).not.toHaveBeenCalled();
  });

  it('replaces or hides the footer', () => {
    renderDialog({ footer: null, actions: [{ key: 'a', label: 'a' }] });

    expect(footerButtons()).toEqual([]);
  });
});
