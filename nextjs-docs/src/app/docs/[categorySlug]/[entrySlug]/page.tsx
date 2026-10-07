'use client';

import { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import { api } from '@/lib/api';
import { Entry, EntryDetail, Category } from '@/types/docs';
import MainContent from '@/components/main-content';
import RightToc from '@/components/right-toc';
import Link from 'next/link';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { parseLayoutStructure, type LayoutNode } from '@/lib/layout-structure';

function flattenEntryIds(nodes: LayoutNode[] | null): number[] {
  if (!nodes || nodes.length === 0) return [];
  let ids: number[] = [];
  for (const node of nodes) {
    if (node.entry_mgmt_id) ids.push(Number(node.entry_mgmt_id));
    if (node.children) {
      ids = ids.concat(flattenEntryIds(node.children));
    }
  }
  return ids;
}

export default function EntryDetailPage() {
  const params = useParams();
  const entrySlug = params?.entrySlug as string;
  const categorySlug = params?.categorySlug as string;

  const [entry, setEntry] = useState<EntryDetail | null>(null);
  const [entries, setEntries] = useState<Entry[]>([]);
  const [flatOrderedIds, setFlatOrderedIds] = useState<number[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    if (entrySlug && categorySlug) {
      const loadData = async () => {
        try {
          const [entryData, entriesData, categoriesData] = await Promise.all([
            api.getEntryDetail(entrySlug) as Promise<EntryDetail>,
            api.getEntriesByCategory(categorySlug) as Promise<Entry[]>,
            api.getCategories() as Promise<Category[]>,
          ]);

          setEntry(entryData);
          setEntries(entriesData);

          const category = categoriesData.find((c) => c.slug === categorySlug);
          if (category) {
            const layoutTree = parseLayoutStructure(category.layout_structure);
            setFlatOrderedIds(flattenEntryIds(layoutTree));
          }
        } catch (error) {
          console.error('Failed to load entry data:', error);
        } finally {
          setIsLoading(false);
        }
      };

      loadData();
    }
  }, [entrySlug, categorySlug]);

  if (isLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
      </div>
    );
  }

  if (!entry) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <p>Entry not found</p>
      </div>
    );
  }

  // Find current index based on layout_structure flattened list
  let prevEntry: Entry | null = null;
  let nextEntry: Entry | null = null;

  if (flatOrderedIds.length > 0) {
    const currentIndex = flatOrderedIds.findIndex((id) => id === entry.id);
    if (currentIndex !== -1) {
      if (currentIndex > 0) {
        const prevId = flatOrderedIds[currentIndex - 1];
        prevEntry = entries.find((e) => e.id === prevId) || null;
      }
      if (currentIndex < flatOrderedIds.length - 1) {
        const nextId = flatOrderedIds[currentIndex + 1];
        nextEntry = entries.find((e) => e.id === nextId) || null;
      }
    }
  } else {
    // Fallback exactly as before if no layout_structure exists!
    const currentIndex = entries.findIndex((e) => e.id === entry.id);
    if (currentIndex !== -1) {
      if (currentIndex > 0) prevEntry = entries[currentIndex - 1] || null;
      if (currentIndex < entries.length - 1) nextEntry = entries[currentIndex + 1] || null;
    }
  }

  return (
    <div className="flex w-full items-start">
      {/* Main Content - Centered */}
      <div className="min-w-0 flex-1 px-6 py-8 md:px-12 lg:px-16">
        <div className="mx-auto max-w-4xl">
          <MainContent entry={entry} />

          {/* Pagination Buttons */}
          <div className="mt-12 flex flex-col items-center justify-between gap-4 border-t border-border pt-8 sm:flex-row">
            {prevEntry ? (
              <Link
                href={`/docs/${categorySlug}/${prevEntry.slug}`}
                className="group flex w-full flex-col items-start gap-1 rounded-lg border border-border/40 p-4 transition-all hover:border-border hover:bg-accent sm:max-w-[48%]"
              >
                <span className="flex items-center gap-1 text-xs text-muted-foreground transition-colors group-hover:text-foreground">
                  <ChevronLeft className="h-3 w-3" /> Previous
                </span>
                <span className="w-full truncate text-sm font-medium text-foreground">
                  {prevEntry.name}
                </span>
              </Link>
            ) : (
              <div className="hidden sm:block" />
            )}

            {nextEntry && (
              <Link
                href={`/docs/${categorySlug}/${nextEntry.slug}`}
                className="group flex w-full flex-col items-end gap-1 rounded-lg border border-border/40 p-4 text-right transition-all hover:border-border hover:bg-accent sm:max-w-[48%]"
              >
                <span className="flex items-center gap-1 text-xs text-muted-foreground transition-colors group-hover:text-foreground">
                  Next <ChevronRight className="h-3 w-3" />
                </span>
                <span className="w-full truncate text-sm font-medium text-foreground">
                  {nextEntry.name}
                </span>
              </Link>
            )}
          </div>
        </div>
      </div>

      {/* Right Sidebar - TOC - Sticky with isolated scroll */}
      <aside className="scrollbar-hide sticky top-16 hidden h-[calc(100vh-4rem)] w-72 shrink-0 overflow-y-auto overscroll-contain py-8 lg:w-80 xl:block">
        <RightToc descriptions={entry.descriptions} layoutStructure={entry.layout_structure} />
      </aside>
    </div>
  );
}
