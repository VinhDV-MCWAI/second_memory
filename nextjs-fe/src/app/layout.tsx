'use client';

import "./globals.css";
import { Providers } from './providers';
import { MetadataManager } from '@/components/common/metadata-manager';
import { useEffect, useState } from 'react';

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const [messages, setMessages] = useState<any>({});
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // Load messages on client side
    const loadMessages = async () => {
      try {
        const locale = localStorage.getItem('locale') || 'en';
        const msgs = await import(`@/../messages/${locale}.json`);
        setMessages(msgs.default);
      } catch (error) {
        // Fallback to English
        const msgs = await import(`@/../messages/en.json`);
        setMessages(msgs.default);
      } finally {
        setIsLoading(false);
      }
    };

    loadMessages();
  }, []);

  if (isLoading) {
    return (
      <html lang="en" suppressHydrationWarning>
        <body className="font-sans antialiased">
          <div className="flex min-h-screen items-center justify-center">
            <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
          </div>
        </body>
      </html>
    );
  }

  return (
    <html lang="en" suppressHydrationWarning>
      <body className="font-sans antialiased">
        <Providers messages={messages}>
          <MetadataManager />
          {children}
        </Providers>
      </body>
    </html>
  );
}
