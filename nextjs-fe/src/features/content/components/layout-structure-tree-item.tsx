'use client';

import { useEffect, useRef } from 'react';
import {
  Trash2,
  ChevronRight,
  ChevronDown,
  GripVertical,
  IndentDecrease,
  IndentIncrease,
} from 'lucide-react';
import {
  draggable,
  dropTargetForElements,
} from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import { combine } from '@atlaskit/pragmatic-drag-and-drop/combine';
import {
  attachClosestEdge,
  type Edge,
} from '@atlaskit/pragmatic-drag-and-drop-hitbox/closest-edge';
import { Button } from '@/components/ui/button';
import type { LayoutStructureItem } from '@/shared/types/api';
import { cn } from '@/shared/utils';
import { INDENT_WIDTH, type DragData } from './layout-structure-tree';

interface TreeItemProps {
  item: LayoutStructureItem;
  depth: number;
  collapsed: Set<string>;
  isDragging: boolean;
  closestEdge: Edge | null;
  onRemove: (id: string) => void;
  onToggleCollapse: (id: string) => void;
  onIndent: (id: string) => void;
  onOutdent: (id: string) => void;
  canIndent: boolean;
  canOutdent: boolean;
  draggingId: string | null;
  draggedOverId: string | null;
}

export function TreeItem({
  item,
  depth,
  collapsed,
  isDragging,
  closestEdge,
  onRemove,
  onToggleCollapse,
  onIndent,
  onOutdent,
  canIndent,
  canOutdent,
  draggingId,
  draggedOverId,
}: TreeItemProps) {
  const ref = useRef<HTMLDivElement>(null);
  const dragHandleRef = useRef<HTMLDivElement>(null);

  const hasChildren = item.children && item.children.length > 0;
  const isCollapsed = collapsed.has(item.ui_id);

  useEffect(() => {
    const element = ref.current;
    const dragHandle = dragHandleRef.current;
    if (!element || !dragHandle) return;

    const dragData: DragData = {
      type: 'tree-item',
      itemId: item.ui_id,
      depth,
      parentId: item.children?.[0]?.ui_id || null,
    };

    return combine(
      draggable({
        element: dragHandle,
        getInitialData: () => dragData as Record<string, unknown>,
        onDragStart: () => {
          // Visual feedback handled by isDragging state
        },
      }),
      dropTargetForElements({
        element,
        getData: ({ input, element }) => {
          return attachClosestEdge(dragData as Record<string, unknown>, {
            input,
            element,
            allowedEdges: ['top', 'bottom'],
          });
        },
        canDrop: ({ source }) => {
          const sourceData = source.data as unknown as DragData;
          return sourceData.type === 'tree-item' && sourceData.itemId !== item.ui_id;
        },
      }),
    );
  }, [item.ui_id, item.children, depth]);

  return (
    <div className="select-none">
      <div
        ref={ref}
        className={cn(
          'mb-1 flex items-center gap-2 rounded-lg border bg-card p-2.5 transition-all duration-200',
          !isDragging && 'hover:bg-accent hover:shadow-sm',
          isDragging && 'opacity-40',
          closestEdge === 'top' && 'border-t-2 border-t-primary',
          closestEdge === 'bottom' && 'border-b-2 border-b-primary',
        )}
        style={{ marginLeft: `${depth * INDENT_WIDTH}px` }}
      >
        <div
          ref={dragHandleRef}
          className="flex-shrink-0 cursor-grab touch-none active:cursor-grabbing"
        >
          <GripVertical className="h-4 w-4 text-muted-foreground" />
        </div>

        {hasChildren ? (
          <button
            onClick={(e) => {
              e.stopPropagation();
              onToggleCollapse(item.ui_id);
            }}
            className="flex-shrink-0 rounded p-0.5 transition-colors hover:bg-accent/50"
            type="button"
          >
            {isCollapsed ? (
              <ChevronRight className="h-4 w-4 text-foreground" />
            ) : (
              <ChevronDown className="h-4 w-4 text-foreground" />
            )}
          </button>
        ) : (
          <div className="w-5" />
        )}

        <div className="flex-1 truncate text-sm font-medium text-foreground">
          {item.name || `Item ${item.entry_desc_id || item.entry_mgmt_id || 'Unknown'}`}
        </div>

        <div className="flex flex-shrink-0 items-center gap-1">
          {canOutdent && (
            <Button
              variant="ghost"
              size="icon"
              onClick={(e) => {
                e.stopPropagation();
                onOutdent(item.ui_id);
              }}
              title="Move Left"
              className="h-7 w-7 transition-colors hover:bg-muted"
              type="button"
            >
              <IndentDecrease className="h-3.5 w-3.5" />
            </Button>
          )}

          {canIndent && (
            <Button
              variant="ghost"
              size="icon"
              onClick={(e) => {
                e.stopPropagation();
                onIndent(item.ui_id);
              }}
              title="Move Right"
              className="h-7 w-7 transition-colors hover:bg-muted"
              type="button"
            >
              <IndentIncrease className="h-3.5 w-3.5" />
            </Button>
          )}

          <Button
            variant="ghost"
            size="icon"
            onClick={(e) => {
              e.stopPropagation();
              onRemove(item.ui_id);
            }}
            className="h-7 w-7 transition-colors hover:bg-destructive/10"
            type="button"
          >
            <Trash2 className="h-3.5 w-3.5 text-destructive" />
          </Button>
        </div>
      </div>

      {hasChildren && !isCollapsed && (
        <div className="mt-0.5">
          {item.children!.map((child, index) => (
            <TreeItem
              key={child.ui_id}
              item={child}
              depth={depth + 1}
              collapsed={collapsed}
              isDragging={draggingId === child.ui_id}
              closestEdge={draggedOverId === child.ui_id ? closestEdge : null}
              onRemove={onRemove}
              onToggleCollapse={onToggleCollapse}
              onIndent={onIndent}
              onOutdent={onOutdent}
              canIndent={index > 0}
              canOutdent={true}
              draggingId={draggingId}
              draggedOverId={draggedOverId}
            />
          ))}
        </div>
      )}
    </div>
  );
}
