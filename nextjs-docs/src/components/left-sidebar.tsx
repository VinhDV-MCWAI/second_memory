'use client';

import { Entry } from '@/types/docs';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { cn } from '@/lib/utils';
import { ChevronLeft } from 'lucide-react';
import { useMemo } from 'react';
import {
  parseLayoutStructure,
  type LayoutNode,
  type RawLayoutStructure,
} from '@/lib/layout-structure';

interface LeftSidebarProps {
  entries: Entry[];
  categorySlug: string;
  layoutStructure?: RawLayoutStructure;
}

export default function LeftSidebar({ entries, categorySlug, layoutStructure }: LeftSidebarProps) {
  const pathname = usePathname();
  const segments = pathname.split('/');
  const activeEntrySlug = segments[segments.length - 1];

  const layoutTree = useMemo(() => parseLayoutStructure(layoutStructure), [layoutStructure]);

  const renderTree = (nodes: LayoutNode[], depth = 0) => {
    return (
      <div
        className={cn('flex flex-col gap-1', depth > 0 && 'mt-1 border-l border-border/50 pl-4')}
      >
        {nodes.map((node, index) => {
          const entryId = node.entry_mgmt_id;
          if (!entryId) return null;

          const entry = entries.find((e) => e.id === Number(entryId));
          if (!entry) return null;

          const isActive = entry.slug === activeEntrySlug;

          return (
            <div key={node.ui_id || `node-${entryId}-${index}`} className="flex flex-col">
              <Link
                href={`/docs/${categorySlug}/${entry.slug}`}
                className={cn(
                  'block rounded-md px-3 py-2 text-sm transition-colors',
                  isActive
                    ? 'bg-accent font-medium text-blue-600 dark:text-blue-400'
                    : 'text-muted-foreground hover:bg-accent hover:text-foreground',
                )}
              >
                <span className="block leading-relaxed break-words whitespace-normal">
                  {entry.name}
                </span>
              </Link>
              {node.children && node.children.length > 0 && renderTree(node.children, depth + 1)}
            </div>
          );
        })}
      </div>
    );
  };

  return (
    <div className="p-6">
      <Link
        href="/docs"
        className="mb-6 flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
      >
        <ChevronLeft className="h-4 w-4" />
        Back to Home
      </Link>

      <div className="space-y-4">
        <h4 className="mb-2 px-2 text-sm font-semibold text-foreground">Entries</h4>
        <nav className="flex flex-col gap-1 pr-4">
          {layoutTree && renderTree(layoutTree)}
          {!layoutTree?.length && (
            <p className="px-3 text-sm text-muted-foreground italic">No entries found.</p>
          )}
        </nav>
      </div>
    </div>
  );
}
