'use client';

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { api } from "@/lib/api";
import { Entry, EntryDetail, Category } from "@/types/docs";
import MainContent from "@/components/main-content";
import RightToc from "@/components/right-toc";
import Link from "next/link";
import { ChevronLeft, ChevronRight } from "lucide-react";

type LayoutNode = { ui_id?: string; entry_mgmt_id?: number | string; children?: LayoutNode[] };

function parseLayoutStructure(raw: any): LayoutNode[] | null {
  if (!raw) return null;
  if (Array.isArray(raw)) return raw;
  if (typeof raw === 'string') {
    try {
      return JSON.parse(raw);
    } catch {
      return null;
    }
  }
  return null;
}

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

          const category = categoriesData.find(c => c.slug === categorySlug);
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
      <div className="min-h-screen flex items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
      </div>
    );
  }

  if (!entry) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <p>Entry not found</p>
      </div>
    );
  }

  // Find current index based on layout_structure flattened list
  let prevEntry: Entry | null = null;
  let nextEntry: Entry | null = null;

  if (flatOrderedIds.length > 0) {
    const currentIndex = flatOrderedIds.findIndex(id => id === entry.id);
    if (currentIndex !== -1) {
      if (currentIndex > 0) {
        const prevId = flatOrderedIds[currentIndex - 1];
        prevEntry = entries.find(e => e.id === prevId) || null;
      }
      if (currentIndex < flatOrderedIds.length - 1) {
        const nextId = flatOrderedIds[currentIndex + 1];
        nextEntry = entries.find(e => e.id === nextId) || null;
      }
    }
  } else {
    // Fallback exactly as before if no layout_structure exists!
    const currentIndex = entries.findIndex(e => e.id === entry.id);
    if (currentIndex !== -1) {
      if (currentIndex > 0) prevEntry = entries[currentIndex - 1] || null;
      if (currentIndex < entries.length - 1) nextEntry = entries[currentIndex + 1] || null;
    }
  }

  return (
    <div className="flex w-full items-start">
      {/* Main Content - Centered */}
      <div className="flex-1 min-w-0 py-8 px-6 md:px-12 lg:px-16">
        <div className="max-w-4xl mx-auto">
          <MainContent entry={entry} />
          
          {/* Pagination Buttons */}
          <div className="flex flex-col sm:flex-row items-center justify-between pt-8 mt-12 border-t border-border gap-4">
            {prevEntry ? (
              <Link
                href={`/docs/${categorySlug}/${prevEntry.slug}`}
                className="group flex flex-col items-start gap-1 p-4 rounded-lg border border-border/40 hover:border-border hover:bg-accent transition-all w-full sm:max-w-[48%]"
              >
                <span className="text-xs text-muted-foreground flex items-center gap-1 group-hover:text-foreground transition-colors">
                  <ChevronLeft className="h-3 w-3" /> Previous
                </span>
                <span className="text-sm font-medium text-foreground truncate w-full">
                  {prevEntry.name}
                </span>
              </Link>
            ) : (
              <div className="hidden sm:block" />
            )}

            {nextEntry && (
              <Link
                href={`/docs/${categorySlug}/${nextEntry.slug}`}
                className="group flex flex-col items-end gap-1 p-4 rounded-lg border border-border/40 hover:border-border hover:bg-accent transition-all text-right w-full sm:max-w-[48%]"
              >
                <span className="text-xs text-muted-foreground flex items-center gap-1 group-hover:text-foreground transition-colors">
                  Next <ChevronRight className="h-3 w-3" />
                </span>
                <span className="text-sm font-medium text-foreground truncate w-full">
                  {nextEntry.name}
                </span>
              </Link>
            )}
          </div>
        </div>
      </div>

      {/* Right Sidebar - TOC - Sticky with isolated scroll */}
      <aside className="hidden xl:block w-72 lg:w-80 shrink-0 sticky top-16 h-[calc(100vh-4rem)] overflow-y-auto overscroll-contain scrollbar-hide py-8">
        <RightToc descriptions={entry.descriptions} layoutStructure={entry.layout_structure} />
      </aside>
    </div>
  );
}
