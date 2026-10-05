"use client";

import { Entry } from "@/types/docs";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";
import { ChevronLeft } from "lucide-react";
import { useMemo } from "react";

interface LeftSidebarProps {
  entries: Entry[];
  categorySlug: string;
  layoutStructure?: any;
}

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

export default function LeftSidebar({
  entries,
  categorySlug,
  layoutStructure,
}: LeftSidebarProps) {
  const pathname = usePathname();
  const segments = pathname.split("/");
  const activeEntrySlug = segments[segments.length - 1];

  const layoutTree = useMemo(() => parseLayoutStructure(layoutStructure), [layoutStructure]);

  const renderTree = (nodes: LayoutNode[], depth = 0) => {
    return (
      <div className={cn("flex flex-col gap-1", depth > 0 && "pl-4 mt-1 border-l border-border/50")}>
        {nodes.map((node, index) => {
          const entryId = node.entry_mgmt_id;
          if (!entryId) return null;
          
          const entry = entries.find(e => e.id === Number(entryId));
          if (!entry) return null;
          
          const isActive = entry.slug === activeEntrySlug;

          return (
            <div key={node.ui_id || `node-${entryId}-${index}`} className="flex flex-col">
              <Link
                href={`/docs/${categorySlug}/${entry.slug}`}
                className={cn(
                  "block px-3 py-2 text-sm transition-colors rounded-md",
                  isActive
                    ? "bg-accent font-medium text-blue-600 dark:text-blue-400"
                    : "text-muted-foreground hover:bg-accent hover:text-foreground"
                )}
              >
                <span className="block leading-relaxed whitespace-normal break-words">
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
        className="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-6"
      >
        <ChevronLeft className="h-4 w-4" />
        Back to Home
      </Link>

      <div className="space-y-4">
        <h4 className="text-sm font-semibold text-foreground px-2 mb-2">
          Entries
        </h4>
        <nav className="flex flex-col gap-1 pr-4">
          {layoutTree && renderTree(layoutTree)}
          {!layoutTree?.length && (
            <p className="text-sm text-muted-foreground px-3 italic">
              No entries found.
            </p>
          )}
        </nav>
      </div>
    </div>
  );
}
