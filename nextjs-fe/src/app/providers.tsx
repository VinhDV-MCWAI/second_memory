'use client';

import { QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { Toaster } from 'react-hot-toast';
import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { setNavigateFunction, clearNavigateFunction } from '@/shared/utils/navigation';
import { QueryClient } from '@tanstack/react-query';
import { ThemeProvider } from 'next-themes';
import { NextIntlClientProvider, AbstractIntlMessages } from 'next-intl';

// Create query client instance
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30 * 1000,
      gcTime: 5 * 60 * 1000,
      retry: 1,
      refetchOnWindowFocus: true,
      refetchOnMount: false,
      refetchOnReconnect: true,
    },
  },
});

function NavigationProvider() {
  const router = useRouter();

  useEffect(() => {
    // Set navigation function for use in sagas and interceptors
    setNavigateFunction((path: string) => {
      router.push(path);
    });

    return () => {
      clearNavigateFunction();
    };
  }, [router]);

  return null;
}

import { AuthProvider } from '@/providers/auth-provider';
import { GlobalLoadingProvider } from '@/shared/providers/global-loading-provider';

// WebSocketNotification removed - WebSocket connections are now handled
// per upload session in HeavyUploadNotification component

export function Providers({ 
  children,
  locale = 'en',
  messages
}: { 
  children: React.ReactNode;
  locale?: string;
  messages: AbstractIntlMessages;
}) {

  const [currentLocale, setCurrentLocale] = useState(locale);
  const [currentMessages, setCurrentMessages] = useState(messages);
  const [isLoaded, setIsLoaded] = useState(true); // Default to true since we have initial messages

  useEffect(() => {
    // Sync messages from prop if available (handles HMR and server-side updates)
    if (messages && locale === currentLocale) {
      setCurrentMessages(messages);
    }
  }, [messages, locale, currentLocale]);

  useEffect(() => {
    // Check for saved locale in localStorage only on mount
    const savedLocale = localStorage.getItem('locale');
    
    if (savedLocale && savedLocale !== locale) {
      setIsLoaded(false);
      setCurrentLocale(savedLocale);
      
      import(`@/../messages/${savedLocale}.json`)
        .then((m) => {
          setCurrentMessages(m.default);
          setIsLoaded(true);
        })
        .catch(() => {
          // Fallback to default if loading fails
          setIsLoaded(true);
        });
    }
  }, [locale]);

  if (!isLoaded) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
      </div>
    );
  }

  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <NextIntlClientProvider messages={currentMessages} locale={currentLocale} timeZone="UTC">
          <ThemeProvider
            attribute="class"
            defaultTheme="system"
            enableSystem
            disableTransitionOnChange={false}
          >
            <GlobalLoadingProvider>
              <NavigationProvider />
              {children}
            </GlobalLoadingProvider>
            <Toaster
              position="top-right"
              reverseOrder={false}
              gutter={8}
              toastOptions={{
                duration: 4000,
                style: {
                  background: '#fff',
                  color: '#000',
                },
                success: {
                  style: {
                    background: '#ecfdf5',
                    color: '#065f46',
                    border: '1px solid #86efac',
                  },
                },
                error: {
                  style: {
                    background: '#fef2f2',
                    color: '#7f1d1d',
                    border: '1px solid #fca5a5',
                  },
                },
              }}
            />
          </ThemeProvider>
        </NextIntlClientProvider>
      </AuthProvider>
      {/* React Query Devtools - only in development */}
      {process.env.NODE_ENV === 'development' && (
        <ReactQueryDevtools initialIsOpen={false} />
      )}
    </QueryClientProvider>
  );
}
