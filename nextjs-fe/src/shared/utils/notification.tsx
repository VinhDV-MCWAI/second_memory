import toast, { Toast, ToastOptions as HotToastOptions } from 'react-hot-toast';

export interface ToastOptions {
  duration?: number;
  position?: HotToastOptions['position'];
}

const renderDismissibleContent = (message: string, t: Toast) => (
  <div
    onClick={() => toast.dismiss(t.id)}
    className="w-full h-full cursor-pointer flex items-center"
    role="button"
    tabIndex={0}
  >
    {message}
  </div>
);

export const notification = {
  success: (message: string, options?: ToastOptions) => {
    toast.success((t) => renderDismissibleContent(message, t), {
      duration: options?.duration || 4000,
      position: options?.position || 'top-right',
    });
  },

  error: (message: string, options?: ToastOptions) => {
    toast.error((t) => renderDismissibleContent(message, t), {
      duration: options?.duration || 4000,
      position: options?.position || 'top-right',
    });
  },

  loading: (message: string, options?: ToastOptions) => {
    return toast.loading((t) => renderDismissibleContent(message, t), {
      duration: options?.duration || 4000,
      position: options?.position || 'top-right',
    });
  },

  promise: <T,>(
    promise: Promise<T>,
    messages: {
      loading: string;
      success: string;
      error: string;
    },
    options?: ToastOptions
  ) => {
    return toast.promise(promise, messages, {
      duration: options?.duration || 4000,
      position: options?.position || 'top-right',
    });
  },

  custom: (message: string, options?: ToastOptions) => {
    return toast((t) => renderDismissibleContent(message, t), {
      duration: options?.duration || 4000,
      position: options?.position || 'top-right',
    });
  },

  info: (message: string, options?: ToastOptions) => {
    return toast((t) => renderDismissibleContent(message, t), {
      icon: 'ℹ️',
      duration: options?.duration || 4000,
      position: options?.position || 'top-right',
      style: {
        border: '1px solid #3b82f6',
        padding: '16px',
        color: '#3b82f6',
      },
    });
  },

  dismiss: (toastId?: string) => {
    if (toastId) {
      toast.dismiss(toastId);
    } else {
      toast.dismiss();
    }
  },
};
