'use client';

import React from 'react';
import { Home, ChevronRight } from 'lucide-react';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { cn } from "@/shared/utils";
import { ADMIN_ROUTES } from '@/shared/config';
import type { PageHeaderProps } from '@/shared/types/ui.types';

export function PageHeader({
  title,
  description,
  breadcrumbs,
  action,
  showBackButton = false,
  onBackClick,
  className,
}: PageHeaderProps) {
  return (
    <div
      className={cn(
        'border-b border-slate-200 bg-white px-4 py-4 dark:border-slate-800 dark:bg-slate-950 lg:px-6',
        className
      )}
    >
      {/* Breadcrumb */}
      {breadcrumbs && breadcrumbs.length > 0 && (
        <div className="mb-4">
          <Breadcrumb>
            <BreadcrumbList>
              {/* Home Icon */}
              <BreadcrumbItem>
                <BreadcrumbLink href={ADMIN_ROUTES.DASHBOARD} className="flex items-center gap-1">
                  <Home className="h-4 w-4" />
                </BreadcrumbLink>
              </BreadcrumbItem>
              <BreadcrumbSeparator>
                <ChevronRight className="h-4 w-4" />
              </BreadcrumbSeparator>

              {breadcrumbs.map((item, index) => (
                <React.Fragment key={`${item.label}-${index}`}>
                  <BreadcrumbItem>
                    {item.href && !item.isActive ? (
                      <BreadcrumbLink href={item.href}>
                        {item.label}
                      </BreadcrumbLink>
                    ) : (
                      <BreadcrumbPage className="font-medium">
                        {item.label}
                      </BreadcrumbPage>
                    )}
                  </BreadcrumbItem>
                  {index < breadcrumbs.length - 1 && (
                    <BreadcrumbSeparator>
                      <ChevronRight className="h-4 w-4" />
                    </BreadcrumbSeparator>
                  )}
                </React.Fragment>
              ))}
            </BreadcrumbList>
          </Breadcrumb>
        </div>
      )}

      {/* Header Content */}
      <div className="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div className="flex flex-1 items-center gap-4">
          {/* Back Button */}
          {showBackButton && (
            <Button
              variant="outline"
              size="icon"
              onClick={onBackClick}
              className="shrink-0"
              aria-label="Go back"
            >
              <ChevronRight className="h-5 w-5 rotate-180" />
            </Button>
          )}

          {/* Title & Description */}
          <div className="flex-1 min-w-0">
            <h1 className="text-3xl font-bold text-slate-900 dark:text-white truncate">
              {title}
            </h1>
            {description && (
              <p className="mt-1 text-slate-600 dark:text-slate-400 line-clamp-2">
                {description}
              </p>
            )}
          </div>
        </div>

        {/* Action Buttons */}
        {action && (
          <div className="flex shrink-0 items-center gap-2 sm:ml-4">
            {action}
          </div>
        )}
      </div>
    </div>
  );
}

// Skeleton loading state component
export function PageHeaderSkeleton() {
  return (
    <div className="border-b border-slate-200 bg-white px-4 py-4 dark:border-slate-800 dark:bg-slate-950 lg:px-6">
      <div className="mb-4 h-4 w-48 animate-pulse rounded bg-slate-200 dark:bg-slate-800" />
      <div className="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div className="flex-1 space-y-2">
          <div className="h-8 w-64 animate-pulse rounded bg-slate-200 dark:bg-slate-800" />
          <div className="h-4 w-96 animate-pulse rounded bg-slate-200 dark:bg-slate-800" />
        </div>
        <div className="h-10 w-32 animate-pulse rounded bg-slate-200 dark:bg-slate-800" />
      </div>
    </div>
  );
}
