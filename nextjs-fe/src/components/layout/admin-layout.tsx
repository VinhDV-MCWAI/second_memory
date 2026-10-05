"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/shared/hooks";
import { Sidebar } from "./sidebar";
import { Header } from "./header";
import { Content } from "./content";
import type { AdminLayoutProps } from '@/shared/types/ui.types';
import { useTranslations } from 'next-intl';

export function AdminLayout({ children, className }: AdminLayoutProps) {
  const router = useRouter();
  const { isAuthenticated, isLoading } = useAuth();
  const tCommon = useTranslations('common');
  const [isMounted, setIsMounted] = useState(false);

  // Fix hydration error: Only render after client-side mount
  useEffect(() => {
    const timer = requestAnimationFrame(() => {
      setIsMounted(true);
    });
    return () => cancelAnimationFrame(timer);
  }, []);

  useEffect(() => {
    if (!isLoading && !isAuthenticated) {
      router.push("/login");
    }
  }, [isLoading, isAuthenticated, router]);

  // Prevent hydration mismatch: Don't render anything until mounted
  if (!isMounted) {
    return null;
  }

  // Show loading while auth is initializing
  if (isLoading) {
    return (
      <div className="flex h-screen items-center justify-center bg-white dark:bg-slate-950">
        <div className="text-center">
          <div className="mb-4 inline-block h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-blue-600 dark:border-slate-800 dark:border-t-blue-400" />
          <p className="text-sm text-slate-600 dark:text-slate-400">
            {tCommon('loading')}
          </p>
        </div>
      </div>
    );
  }

  // If not authenticated, don't render content (redirect will happen)
  if (!isAuthenticated) {
    return null;
  }

  return (
    <div className="flex h-screen bg-white dark:bg-slate-950">
      {/* Sidebar */}
      <Sidebar />

      {/* Main Content */}
      <div className="flex flex-1 flex-col lg:ml-64">
        {/* Header */}
        <Header />

        {/* Content Area */}
        <Content className={className}>{children}</Content>
      </div>
    </div>
  );
}
