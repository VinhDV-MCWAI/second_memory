'use client';

import { Description, EntryDetail } from '@/types/docs';
import { slugify, cn } from '@/lib/utils';
import { useEffect, useState, useMemo } from 'react';
import { useScrollSpy } from '@/hooks/use-scroll-spy';
import {
  parseLayoutStructure,
  type LayoutNode,
  type RawLayoutStructure,
} from '@/lib/layout-structure';

interface RightTocProps {
  descriptions: Description[];
  layoutStructure?: RawLayoutStructure;
}

export default function RightToc({ descriptions, layoutStructure }: RightTocProps) {
  const tocItems = useMemo<{ id: string; title: string }[]>(
    () =>
      descriptions.map((desc) => ({
        id: slugify(desc.title),
        title: desc.title,
      })),
    [descriptions],
  );

  const ids = useMemo<string[]>(() => tocItems.map((item: { id: string }) => item.id), [tocItems]);

  const activeId = useScrollSpy(ids, {
    rootMargin: '-20px 0px -80% 0px', // Trigger when top of section is near the top
  });

  const handleClick = (e: React.MouseEvent<HTMLAnchorElement>, id: string) => {
    e.preventDefault();
    const element = document.getElementById(id);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' });
      window.history.pushState(null, '', `#${id}`);
    }
  };

  const layoutTree = useMemo(() => parseLayoutStructure(layoutStructure), [layoutStructure]);

  const renderTree = (nodes: LayoutNode[], depth = 0) => {
    return (
      <div
        className={cn('flex flex-col gap-2', depth > 0 && 'mt-2 border-l border-border/50 pl-3')}
      >
        {nodes.map((node, index) => {
          const descId = node.entry_desc_id;
          if (!descId) return null;

          const itemDesc = descriptions.find((d) => d.id === Number(descId));
          if (!itemDesc) return null;

          const itemId = slugify(itemDesc.title);
          const isActive = activeId === itemId;

          return (
            <div key={node.ui_id || `node-${descId}-${index}`} className="flex flex-col">
              <a
                href={`#${itemId}`}
                onClick={(e) => handleClick(e, itemId)}
                className={cn(
                  'group block py-1 text-[13px] leading-relaxed break-words whitespace-normal transition-colors',
                  isActive
                    ? 'font-medium text-blue-600 dark:text-blue-400'
                    : 'text-muted-foreground hover:text-foreground',
                )}
              >
                {itemDesc.title}
              </a>
              {node.children && node.children.length > 0 && renderTree(node.children, depth + 1)}
            </div>
          );
        })}
      </div>
    );
  };

  return (
    <div className="py-2 pr-6 pl-4">
      <h4 className="mb-4 text-sm font-semibold text-foreground">On This Page</h4>
      <nav className="flex flex-col gap-3">
        {layoutTree && layoutTree.length > 0 ? (
          renderTree(layoutTree)
        ) : (
          <div className="flex flex-col gap-2">
            {descriptions.map((itemDesc) => {
              const itemId = slugify(itemDesc.title);
              const isActive = activeId === itemId;
              return (
                <a
                  key={`fallback-toc-${itemDesc.id}`}
                  href={`#${itemId}`}
                  onClick={(e) => handleClick(e, itemId)}
                  className={cn(
                    'group block py-1 text-[13px] leading-relaxed break-words whitespace-normal transition-colors',
                    isActive
                      ? 'font-medium text-blue-600 dark:text-blue-400'
                      : 'text-muted-foreground hover:text-foreground',
                  )}
                >
                  {itemDesc.title}
                </a>
              );
            })}
          </div>
        )}
      </nav>
    </div>
  );
}
