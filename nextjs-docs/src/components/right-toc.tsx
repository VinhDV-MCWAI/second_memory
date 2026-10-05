"use client";

import { Description, EntryDetail } from "@/types/docs";
import { slugify, cn } from "@/lib/utils";
import { useEffect, useState, useMemo } from "react";
import { useScrollSpy } from "@/hooks/use-scroll-spy";

interface RightTocProps {
  descriptions: Description[];
  layoutStructure?: any;
}

type LayoutNode = { ui_id?: string; entry_desc_id?: number | string; children?: LayoutNode[] };

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

export default function RightToc({ descriptions, layoutStructure }: RightTocProps) {
  const tocItems = useMemo<{ id: string; title: string }[]>(
    () =>
      descriptions.map((desc) => ({
        id: slugify(desc.title),
        title: desc.title,
      })),
    [descriptions]
  );

  const ids = useMemo<string[]>(
    () => tocItems.map((item: { id: string }) => item.id),
    [tocItems]
  );

  const activeId = useScrollSpy(ids, {
    rootMargin: "-20px 0px -80% 0px", // Trigger when top of section is near the top
  });

  const handleClick = (e: React.MouseEvent<HTMLAnchorElement>, id: string) => {
    e.preventDefault();
    const element = document.getElementById(id);
    if (element) {
      element.scrollIntoView({ behavior: "smooth", block: "start" });
      window.history.pushState(null, "", `#${id}`);
    }
  };

  const layoutTree = useMemo(() => parseLayoutStructure(layoutStructure), [layoutStructure]);

  const renderTree = (nodes: LayoutNode[], depth = 0) => {
    return (
      <div className={cn("flex flex-col gap-2", depth > 0 && "pl-3 mt-2 border-l border-border/50")}>
        {nodes.map((node, index) => {
          const descId = node.entry_desc_id;
          if (!descId) return null;

          const itemDesc = descriptions.find(d => d.id === Number(descId));
          if (!itemDesc) return null;
          
          const itemId = slugify(itemDesc.title);
          const isActive = activeId === itemId;

          return (
            <div key={node.ui_id || `node-${descId}-${index}`} className="flex flex-col">
              <a
                href={`#${itemId}`}
                onClick={(e) => handleClick(e, itemId)}
                className={cn(
                  "group text-[13px] leading-relaxed transition-colors py-1 break-words whitespace-normal block",
                  isActive
                    ? "font-medium text-blue-600 dark:text-blue-400"
                    : "text-muted-foreground hover:text-foreground"
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
    <div className="py-2 pl-4 pr-6">
      <h4 className="text-sm font-semibold text-foreground mb-4">
        On This Page
      </h4>
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
                    "group text-[13px] leading-relaxed transition-colors py-1 break-words whitespace-normal block",
                    isActive
                      ? "font-medium text-blue-600 dark:text-blue-400"
                      : "text-muted-foreground hover:text-foreground"
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
