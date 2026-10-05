
import { UseFormSetError, Path, FieldValues } from 'react-hook-form';

export interface ApiError {
  status: boolean;
  code: number;
  messages: Record<string, string[]>;
}

export interface ApiResponseError {
  response?: {
    data?: {
      error?: ApiError;
      message?: string;
    };
    status?: number;
  };
}

/**
 * Handles backend validation errors and sets them to react-hook-form
 * @param error The API error object
 * @param setError The setError function from react-hook-form
 */
export const handleBindErrors = <T extends FieldValues>(
  error: unknown,
  setError: UseFormSetError<T>
) => {
  const err = error as ApiResponseError;
  if (!err?.response?.data?.error) return;

  const apiError = err.response.data.error;

  if (apiError.code === 422 && apiError.messages) {
    Object.entries(apiError.messages).forEach(([field, messages]) => {
      setError(field as Path<T>, {
        type: 'server',
        message: messages[0], // Display the first error message
      });
    });
  }
};
