import { AxiosError } from 'axios';
import { toast } from 'react-hot-toast/headless';
import { HTTP_STATUS } from '@/shared/config/constant';
import { ApiResponse } from '@/shared/types/api';

const getErrorMessage = (error: AxiosError<ApiResponse<unknown> | { message?: string }>): string => {
  const responseData = error.response?.data;
  
  // Check if it's ApiResponse format
  if (responseData && typeof responseData === 'object' && 'error' in responseData) {
    const apiResponse = responseData as ApiResponse<unknown>;
    if (apiResponse.error?.messages) {
      const messages = apiResponse.error.messages;
      return Array.isArray(messages) ? messages.join(', ') : messages;
    }
  }
  
  // Fallback to old format
  if (responseData && typeof responseData === 'object' && 'message' in responseData) {
    const oldFormat = responseData as { message?: string };
    if (oldFormat.message) {
      return oldFormat.message;
    }
  }

  if (error.message) {
    return error.message;
  }

  return 'errors.E0005';
};

export const handleCommonError = (error: AxiosError<ApiResponse<unknown> | { message?: string }>) => {
  if (!error.response) {
    toast.error('errors.E0004');
    return Promise.reject(error);
  }

  const statusCode = error?.response?.status || 0;
  const errorMessage = getErrorMessage(error);

  switch (statusCode) {
    case HTTP_STATUS.BAD_REQUEST:
      toast.error(errorMessage || 'errors.E0400');
      break;

    case HTTP_STATUS.UNAUTHORIZED:
      toast.error(errorMessage || 'errors.E0401');
      break;

    case HTTP_STATUS.FORBIDDEN:
      toast.error(errorMessage || 'errors.E0403');
      break;

    case HTTP_STATUS.NOT_FOUND:
      toast.error(errorMessage || 'errors.E0404');
      break;

    case HTTP_STATUS.INTERNAL_SERVER_ERROR:
      toast.error(errorMessage || 'errors.E0500');
      break;

    case HTTP_STATUS.UNPROCESSABLE_CONTENT:
      return Promise.reject(error);

    case 502: // BAD_GATEWAY
      toast.error(errorMessage || 'errors.E0502');
      break;

    case 503: // SERVICE_UNAVAILABLE
      toast.error(errorMessage || 'errors.E0503');
      break;

    case 504: // GATEWAY_TIMEOUT
      toast.error(errorMessage || 'errors.E0504');
      break;

    default:
      toast.error(errorMessage || 'errors.E1000');
      break;
  }

  return Promise.reject(error);
};

export default handleCommonError;
