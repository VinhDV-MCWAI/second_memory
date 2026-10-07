import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import toast, { type Toast } from 'react-hot-toast';
import { notification } from './notification';

vi.mock('react-hot-toast', () => {
  const fn = Object.assign(vi.fn(), {
    success: vi.fn(),
    error: vi.fn(),
    loading: vi.fn(),
    promise: vi.fn(),
    dismiss: vi.fn(),
  });
  return { default: fn };
});

type Renderer = (t: Toast) => React.ReactNode;

describe('notification', () => {
  beforeEach(() => vi.clearAllMocks());

  it('uses top-right and 4s by default, overridable per call', () => {
    notification.success('Saved');
    notification.error('Failed', { duration: 1000, position: 'bottom-left' });

    expect(vi.mocked(toast.success).mock.calls[0][1]).toEqual({
      duration: 4000,
      position: 'top-right',
    });
    expect(vi.mocked(toast.error).mock.calls[0][1]).toEqual({
      duration: 1000,
      position: 'bottom-left',
    });
  });

  it('renders the message and dismisses the toast on click', () => {
    notification.info('Processing');
    const [renderContent, options] = vi.mocked(toast).mock.calls[0] as unknown as [
      Renderer,
      { icon: string },
    ];
    expect(options.icon).toBe('ℹ️');

    render(<>{renderContent({ id: 't-1' } as Toast)}</>);
    fireEvent.click(screen.getByRole('button', { name: 'Processing' }));

    expect(toast.dismiss).toHaveBeenCalledWith('t-1');
  });

  it('forwards loading, promise, custom and dismiss', () => {
    const pending = Promise.resolve(1);
    const messages = { loading: 'Saving', success: 'Saved', error: 'Failed' };

    notification.loading('Loading');
    notification.promise(pending, messages);
    notification.custom('Hello');
    notification.dismiss('t-2');
    notification.dismiss();

    expect(toast.loading).toHaveBeenCalledTimes(1);
    expect(toast.promise).toHaveBeenCalledWith(pending, messages, {
      duration: 4000,
      position: 'top-right',
    });
    expect(toast).toHaveBeenCalledTimes(1);
    expect(vi.mocked(toast.dismiss).mock.calls).toEqual([['t-2'], []]);
  });
});
