import { describe, expect, it, vi } from 'vitest';
import { getApiErrorMessage, handleBindErrors } from './error-handler';

const apiError = (code: number, messages: unknown) => ({
  response: { status: code, data: { data: null, error: { status: true, code, messages } } },
});

describe('getApiErrorMessage', () => {
  it('returns the server message string', () => {
    expect(getApiErrorMessage(apiError(403, 'Access is forbidden'))).toBe('Access is forbidden');
  });

  it('joins a message list', () => {
    expect(getApiErrorMessage(apiError(400, ['first', 'second']))).toBe('first, second');
  });

  it('returns the first message of a validation field map', () => {
    expect(
      getApiErrorMessage(apiError(422, { name: ['Name is required'], code: ['Too long'] })),
    ).toBe('Name is required');
  });

  it.each([
    ['network error without response', new Error('Network Error')],
    ['empty message', apiError(500, '')],
    ['null messages', apiError(500, null)],
    ['non-envelope body', { response: { status: 502, data: '<html>Bad Gateway</html>' } }],
    ['undefined', undefined],
  ])('returns undefined for %s', (_, error) => {
    expect(getApiErrorMessage(error)).toBeUndefined();
  });
});

describe('handleBindErrors', () => {
  it('binds each 422 field error to the form', () => {
    const setError = vi.fn();

    handleBindErrors(apiError(422, { name: ['Name is required', 'ignored'] }), setError);

    expect(setError).toHaveBeenCalledOnce();
    expect(setError).toHaveBeenCalledWith('name', { type: 'server', message: 'Name is required' });
  });

  it('ignores non-validation errors', () => {
    const setError = vi.fn();

    handleBindErrors(apiError(403, 'Access is forbidden'), setError);

    expect(setError).not.toHaveBeenCalled();
  });
});
