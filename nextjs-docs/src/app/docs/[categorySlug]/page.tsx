'use client';

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { api } from "@/lib/api";
import { Entry, Category } from "@/types/docs";

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

function getFirstEntryId(nodes: LayoutNode[] | null): number | null {
  if (!nodes || nodes.length === 0) return null;
  for (const node of nodes) {
    if (node.entry_mgmt_id) return Number(node.entry_mgmt_id);
    if (node.children) {
      const id = getFirstEntryId(node.children);
      if (id) return id;
    }
  }
  return null;
}

export default function CategoryPage() {
  const params = useParams();
  const router = useRouter();
  const categorySlug = params?.categorySlug as string;
  const [isLoading, setIsLoading] = useState(true);
  const [isEmpty, setIsEmpty] = useState(false);

  useEffect(() => {
    if (categorySlug) {
      const loadAndRedirect = async () => {
        try {
          const [categories, entries] = await Promise.all([
            api.getCategories() as Promise<Category[]>,
            api.getEntriesByCategory(categorySlug) as Promise<Entry[]>
          ]);

          const category = categories.find(c => c.slug === categorySlug);
          if (!category) {
            setIsEmpty(true);
            return;
          }

          const layoutTree = parseLayoutStructure(category.layout_structure);
          const firstEntryId = getFirstEntryId(layoutTree);
          
          if (firstEntryId) {
            // Redirect to first entry in the layout structure
            const firstEntry = entries.find(e => e.id === firstEntryId);
            if (firstEntry) {
              router.push(`/docs/${categorySlug}/${firstEntry.slug}`);
              return;
            }
          }

          // Fallback if layout_structure is empty but entries exist
          if (entries && entries.length > 0) {
            router.push(`/docs/${categorySlug}/${entries[0].slug}`);
            return;
          }

          setIsEmpty(true);
        } catch (error) {
          console.error('Failed to load entries:', error);
          setIsEmpty(true);
        } finally {
          setIsLoading(false);
        }
      };

      loadAndRedirect();
    }
  }, [categorySlug, router]);

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
      </div>
    );
  }

  if (isEmpty) {
    return (
      <div className="h-full min-h-[50vh] flex flex-col items-center justify-center p-8">
        <h2 className="text-2xl font-bold mb-2">No Entries Found</h2>
        <p className="text-muted-foreground">This category doesn't have any documentation entries yet.</p>
      </div>
    );
  }

  return null;
}
