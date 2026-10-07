import { describe, expect, it, vi } from 'vitest';
import { act, renderHook } from '@testing-library/react';
import { useActionLock } from './useActionLock';

describe('useActionLock', () => {
  it('ignores calls while an action is running', async () => {
    const { result } = renderHook(() => useActionLock());
    let release: () => void = () => {};
    const action = vi.fn(() => new Promise<string>((resolve) => (release = () => resolve('done'))));

    let first: Promise<string | undefined> = Promise.resolve(undefined);
    let second: string | undefined = 'not called';
    await act(async () => {
      first = result.current.execute(action);
      second = await result.current.execute(action);
    });
    expect(result.current.isLoading).toBe(true);
    expect(second).toBeUndefined();

    await act(async () => {
      release();
      await first;
    });
    expect(await first).toBe('done');
    expect(action).toHaveBeenCalledTimes(1);
    expect(result.current.isLoading).toBe(false);
  });

  it('keeps the lock for the cooldown delay', async () => {
    vi.useFakeTimers();
    const { result } = renderHook(() => useActionLock({ delay: 500 }));
    const action = vi.fn(async () => 1);

    await act(async () => {
      await result.current.execute(action);
    });
    await act(async () => {
      await result.current.execute(action);
    });
    expect(action).toHaveBeenCalledTimes(1);

    await act(async () => {
      vi.advanceTimersByTime(500);
    });
    await act(async () => {
      await result.current.execute(action);
    });
    expect(action).toHaveBeenCalledTimes(2);
    vi.useRealTimers();
  });

  it('releases the lock when the action throws', async () => {
    const { result } = renderHook(() => useActionLock());

    await act(async () => {
      await expect(result.current.execute(() => Promise.reject(new Error('boom')))).rejects.toThrow(
        'boom',
      );
    });
    expect(result.current.isLoading).toBe(false);

    let value: number | undefined;
    await act(async () => {
      value = await result.current.execute(async () => 2);
    });
    expect(value).toBe(2);
  });
});
