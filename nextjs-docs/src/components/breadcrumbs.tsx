'use client';

import * as React from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { ChevronRight, Home } from 'lucide-react';
import { Entry } from '@/types/docs';

interface BreadcrumbsProps {
  entries: Entry[];
  categorySlug: string;
}

export default function Breadcrumbs({ entries, categorySlug }: BreadcrumbsProps) {
  const pathname = usePathname();
  const segments = pathname.split('/').filter(Boolean);

  // Find the current entry name
  const currentEntrySlug = segments[segments.length - 1];
  const currentEntry = entries.find((e) => e.slug === currentEntrySlug);

  // Format category name (e.g. getting-started -> Getting Started)
  const categoryName = categorySlug
    .split('-')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');

  return (
    <nav className="scrollbar-hide flex items-center gap-2 overflow-x-auto py-1 text-sm whitespace-nowrap text-muted-foreground">
      <Link
        href="/docs"
        className="flex items-center gap-1 transition-colors hover:text-foreground"
      >
        <Home className="h-3.5 w-3.5" />
        <span>Docs</span>
      </Link>

      <ChevronRight className="Opacity-50 h-3.5 w-3.5 shrink-0" />

      <Link
        href={`/docs/${categorySlug}`}
        className="max-w-[120px] truncate transition-colors hover:text-foreground"
      >
        {categoryName}
      </Link>

      {currentEntry && (
        <>
          <ChevronRight className="Opacity-50 h-3.5 w-3.5 shrink-0" />
          <span className="max-w-[200px] truncate font-medium text-foreground">
            {currentEntry.name}
          </span>
        </>
      )}
    </nav>
  );
}
