import { UseFormSetError, Path, FieldValues } from 'react-hook-form';
import { HTTP_STATUS } from '@/shared/config';

/** `error` part of the backend envelope; `messages` is a field map for 422 responses. */
export interface ApiError {
  status: boolean;
  code: number;
  messages: string | string[] | Record<string, string[]> | null;
}

export interface ApiResponseError {
  response?: {
    data?: {
      error?: ApiError;
    };
    status?: number;
  };
}

const isFieldMap = (
  messages: ApiError['messages'] | undefined,
): messages is Record<string, string[]> =>
  typeof messages === 'object' && messages !== null && !Array.isArray(messages);

/**
 * Extracts the server-provided error message from an API error.
 * Returns undefined when the server sent none (network error, empty body), so callers can
 * fall back to their own translated message.
 */
export const getApiErrorMessage = (error: unknown): string | undefined => {
  const messages = (error as ApiResponseError)?.response?.data?.error?.messages;

  if (typeof messages === 'string') {
    return messages || undefined;
  }

  if (Array.isArray(messages)) {
    return messages.join(', ') || undefined;
  }

  if (isFieldMap(messages)) {
    return Object.values(messages).flat()[0];
  }

  return undefined;
};

/**
 * Handles backend validation errors and sets them to react-hook-form
 * @param error The API error object
 * @param setError The setError function from react-hook-form
 */
export const handleBindErrors = <T extends FieldValues>(
  error: unknown,
  setError: UseFormSetError<T>,
) => {
  const err = error as ApiResponseError;
  if (!err?.response?.data?.error) return;

  const apiError = err.response.data.error;

  if (apiError.code === HTTP_STATUS.UNPROCESSABLE_CONTENT && isFieldMap(apiError.messages)) {
    Object.entries(apiError.messages).forEach(([field, messages]) => {
      setError(field as Path<T>, {
        type: 'server',
        message: messages[0], // Display the first error message
      });
    });
  }
};
