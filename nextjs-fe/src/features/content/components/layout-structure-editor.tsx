'use client';

import { useState, useCallback, useEffect } from 'react';
import { v4 as uuidv4 } from 'uuid';
import { Search } from 'lucide-react';
import { monitorForElements } from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import {
  extractClosestEdge,
  type Edge,
} from '@atlaskit/pragmatic-drag-and-drop-hitbox/closest-edge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { LayoutStructureItem } from '@/shared/types/api';
import {
  enrichWithNames,
  indentItem,
  moveItem,
  outdentItem,
  removeItem,
  type DragData,
} from './layout-structure-tree';
import { TreeItem } from './layout-structure-tree-item';

interface LayoutStructureEditorProps {
  type: 'entry' | 'entry_desc';
  availableItems: Array<{ id: number; name: string; slug?: string }>;
  value?: LayoutStructureItem[];
  onChange: (structure: LayoutStructureItem[]) => void;
  onSearch?: (query: string) => void;
  loading?: boolean;
}

export function LayoutStructureEditor({
  type,
  availableItems,
  value = [],
  onChange,
  onSearch,
  loading,
}: LayoutStructureEditorProps): React.ReactElement {
  const [structure, setStructure] = useState<LayoutStructureItem[]>(value);
  const [searchQuery, setSearchQuery] = useState('');
  const [collapsed, setCollapsed] = useState<Set<string>>(new Set());
  const [draggingId, setDraggingId] = useState<string | null>(null);
  const [draggedOverId, setDraggedOverId] = useState<string | null>(null);
  const [closestEdge, setClosestEdge] = useState<Edge | null>(null);

  // Re-sync the local tree when the form value or the item list changes.
  /* eslint-disable react-hooks/set-state-in-effect */
  useEffect(() => {
    if (!availableItems || availableItems.length === 0) {
      setStructure([]);
      return;
    }
    const enrichedValue = enrichWithNames(value, availableItems);
    setStructure(enrichedValue);

    if (enrichedValue.length !== value.length && onChange) {
      queueMicrotask(() => onChange(enrichedValue));
    }
  }, [value, availableItems, onChange]);
  /* eslint-enable react-hooks/set-state-in-effect */

  const applyChange = useCallback(
    (newStructure: LayoutStructureItem[] | null) => {
      if (!newStructure) return;
      setStructure(newStructure);
      queueMicrotask(() => onChange(newStructure));
    },
    [onChange],
  );

  const handleReorder = useCallback(
    (draggedId: string, targetId: string, edge: Edge | null) =>
      applyChange(moveItem(structure, draggedId, targetId, edge)),
    [structure, applyChange],
  );

  useEffect(() => {
    return monitorForElements({
      onDragStart: ({ source }) => {
        const data = source.data as unknown as DragData;
        if (data.type === 'tree-item') {
          setDraggingId(data.itemId);
        }
      },
      onDrag: ({ location }) => {
        const target = location.current.dropTargets[0];
        if (!target) {
          setDraggedOverId(null);
          setClosestEdge(null);
          return;
        }

        const targetData = target.data as unknown as DragData;
        if (targetData.type === 'tree-item') {
          setDraggedOverId(targetData.itemId);
          const edge = extractClosestEdge(target.data);
          setClosestEdge(edge);
        }
      },
      onDrop: ({ location, source }) => {
        const target = location.current.dropTargets[0];
        if (!target) return;

        const sourceData = source.data as unknown as DragData;
        const targetData = target.data as unknown as DragData;
        const edge = extractClosestEdge(target.data);

        if (sourceData.type === 'tree-item' && targetData.type === 'tree-item') {
          handleReorder(sourceData.itemId, targetData.itemId, edge);
        }

        setDraggingId(null);
        setDraggedOverId(null);
        setClosestEdge(null);
      },
    });
  }, [handleReorder]);

  const handleSearchChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      const query = e.target.value;
      setSearchQuery(query);
      onSearch?.(query);
    },
    [onSearch],
  );

  const handleAddItem = useCallback(
    (item: { id: number; name: string; slug?: string }) => {
      const newItem: LayoutStructureItem = {
        ui_id: uuidv4(),
        ...(type === 'entry_desc' ? { entry_desc_id: item.id } : { entry_mgmt_id: item.id }),
        name: item.name,
        slug: item.slug,
      };
      applyChange([...structure, newItem]);
    },
    [structure, applyChange, type],
  );

  const handleRemoveItem = useCallback(
    (uiId: string) => applyChange(removeItem(structure, uiId)),
    [structure, applyChange],
  );

  const handleToggleCollapse = useCallback((uiId: string) => {
    setCollapsed((prev) => {
      const newSet = new Set(prev);
      if (newSet.has(uiId)) {
        newSet.delete(uiId);
      } else {
        newSet.add(uiId);
      }
      return newSet;
    });
  }, []);

  const handleIndent = useCallback(
    (uiId: string) => applyChange(indentItem(structure, uiId)),
    [structure, applyChange],
  );

  const handleOutdent = useCallback(
    (uiId: string) => applyChange(outdentItem(structure, uiId)),
    [structure, applyChange],
  );

  const filteredAvailableItems = availableItems.filter((item) =>
    item.name.toLowerCase().includes(searchQuery.toLowerCase()),
  );

  return (
    <div className="grid h-full min-h-[400px] grid-cols-2 gap-4">
      {/* Left - Structure */}
      <div className="flex h-full flex-col overflow-hidden rounded-lg border bg-card p-4">
        <Label className="mb-3 shrink-0 text-base font-semibold">Layout Structure</Label>
        <div className="mb-4 shrink-0 space-y-1 rounded-md bg-muted/30 p-3 text-xs text-muted-foreground">
          <div className="font-medium">How to organize items:</div>
          <div>
            • <strong>Drag items</strong> up/down to reorder them
          </div>
          <div>
            • <strong>Use →</strong> button to make an item a child of the item above
          </div>
          <div>
            • <strong>Use ←</strong> button to move an item up one level
          </div>
          <div>
            • <strong>Click chevron (▼/▶)</strong> to collapse/expand children
          </div>
        </div>
        <div className="min-h-0 flex-1 overflow-y-auto px-1">
          {structure.length === 0 ? (
            <div className="py-12 text-center text-sm text-muted-foreground">
              No items yet. Add from the right panel.
            </div>
          ) : (
            <div>
              {structure.map((item, idx) => (
                <TreeItem
                  key={item.ui_id}
                  item={item}
                  depth={0}
                  collapsed={collapsed}
                  isDragging={draggingId === item.ui_id}
                  closestEdge={draggedOverId === item.ui_id ? closestEdge : null}
                  onRemove={handleRemoveItem}
                  onToggleCollapse={handleToggleCollapse}
                  onIndent={handleIndent}
                  onOutdent={handleOutdent}
                  canIndent={idx > 0}
                  canOutdent={false}
                  draggingId={draggingId}
                  draggedOverId={draggedOverId}
                />
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Right - Available Items */}
      <div className="flex h-full flex-col overflow-hidden rounded-lg border bg-card p-4">
        <Label className="mb-3 shrink-0 text-base font-semibold">Available Items</Label>
        <div className="relative mb-3 shrink-0">
          <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder={`Search ${type === 'entry' ? 'entries' : 'descriptions'}...`}
            value={searchQuery}
            onChange={handleSearchChange}
            disabled={loading}
            className="pl-9"
          />
        </div>
        <div className="min-h-0 flex-1 space-y-2 overflow-y-auto px-1">
          {loading ? (
            <div className="py-8 text-center text-sm text-muted-foreground">Loading...</div>
          ) : filteredAvailableItems.length === 0 ? (
            <div className="py-8 text-center text-sm text-muted-foreground">
              {searchQuery ? 'No items found' : 'No items available'}
            </div>
          ) : (
            filteredAvailableItems.map((item) => (
              <Button
                key={item.id}
                variant="outline"
                className="h-auto w-full justify-start py-2.5 text-left"
                onClick={() => handleAddItem(item)}
                disabled={loading}
                type="button"
              >
                <div className="flex w-full items-center gap-2">
                  <div className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded bg-primary/10">
                    <span className="text-xs font-bold text-primary">
                      {item.name.substring(0, 2).toUpperCase()}
                    </span>
                  </div>
                  <div className="flex-1 truncate text-sm">{item.name}</div>
                </div>
              </Button>
            ))
          )}
        </div>
      </div>
    </div>
  );
}
