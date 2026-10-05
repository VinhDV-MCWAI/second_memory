'use client';

import { useState, useCallback, useEffect, useRef } from 'react';
import { v4 as uuidv4 } from 'uuid';
import { Search, Trash2, ChevronRight, ChevronDown, GripVertical, IndentDecrease, IndentIncrease } from 'lucide-react';
import { draggable, dropTargetForElements, monitorForElements } from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import { combine } from '@atlaskit/pragmatic-drag-and-drop/combine';
import { attachClosestEdge, extractClosestEdge, type Edge } from '@atlaskit/pragmatic-drag-and-drop-hitbox/closest-edge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { LayoutStructureItem } from '@/shared/types/api';
import { cn } from '@/shared/utils';

interface LayoutStructureEditorProps {
  type: 'entry' | 'entry_desc';
  availableItems: Array<{ id: number; name: string; slug?: string }>;
  value?: LayoutStructureItem[];
  onChange: (structure: LayoutStructureItem[]) => void;
  onSearch?: (query: string) => void;
  loading?: boolean;
}

interface FlatItem extends LayoutStructureItem {
  depth: number;
  parentId: string | null;
}

interface DragData extends Record<string, unknown> {
  type: 'tree-item';
  itemId: string;
  depth: number;
  parentId: string | null;
}

const INDENT_WIDTH = 32; // pixels per depth level
const MAX_DEPTH = 5; // Maximum nesting level

// Tree manipulation helpers
const treeHelpers = {
  flatten: (items: LayoutStructureItem[], parentId: string | null = null, depth: number = 0): FlatItem[] => {
    const flat: FlatItem[] = [];
    items.forEach(item => {
      flat.push({ ...item, depth, parentId });
      if (item.children?.length) {
        flat.push(...treeHelpers.flatten(item.children, item.ui_id, depth + 1));
      }
    });
    return flat;
  },

  unflatten: (flatItems: FlatItem[]): LayoutStructureItem[] => {
    const map = new Map<string, LayoutStructureItem>();
    const roots: LayoutStructureItem[] = [];

    flatItems.forEach(item => {
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      const { depth: _depth, parentId: _parentId, ...data } = item;
      map.set(item.ui_id, { ...data, children: [] });
    });

    flatItems.forEach(item => {
      const node = map.get(item.ui_id)!;
      if (item.parentId && map.has(item.parentId)) {
        const parent = map.get(item.parentId)!;
        parent.children = parent.children || [];
        parent.children.push(node);
      } else {
        roots.push(node);
      }
    });

    // Clean up empty children arrays
    const cleanup = (items: LayoutStructureItem[]) => {
      items.forEach(item => {
        if (item.children && item.children.length === 0) {
          delete item.children;
        } else if (item.children) {
          cleanup(item.children);
        }
      });
    };
    cleanup(roots);

    return roots;
  },
};

// TreeItem Component
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

function TreeItem({
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
      })
    );
  }, [item.ui_id, item.children, depth]);

  return (
    <div className="select-none">
      <div
        ref={ref}
        className={cn(
          'flex items-center gap-2 p-2.5 mb-1 border rounded-lg bg-card transition-all duration-200',
          !isDragging && 'hover:bg-accent hover:shadow-sm',
          isDragging && 'opacity-40',
          closestEdge === 'top' && 'border-t-2 border-t-primary',
          closestEdge === 'bottom' && 'border-b-2 border-b-primary'
        )}
        style={{ marginLeft: `${depth * INDENT_WIDTH}px` }}
      >
        <div 
          ref={dragHandleRef}
          className="flex-shrink-0 cursor-grab active:cursor-grabbing touch-none"
        >
          <GripVertical className="h-4 w-4 text-muted-foreground" />
        </div>

        {hasChildren ? (
          <button
            onClick={(e) => {
              e.stopPropagation();
              onToggleCollapse(item.ui_id);
            }}
            className="flex-shrink-0 hover:bg-accent/50 rounded p-0.5 transition-colors"
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

        <div className="flex-1 text-sm font-medium truncate text-foreground">
          {item.name || `Item ${item.entry_desc_id || item.entry_mgmt_id || 'Unknown'}`}
        </div>

        <div className="flex items-center gap-1 flex-shrink-0">
          {canOutdent && (
            <Button
              variant="ghost"
              size="icon"
              onClick={(e) => {
                e.stopPropagation();
                onOutdent(item.ui_id);
              }}
              title="Move Left"
              className="h-7 w-7 hover:bg-muted transition-colors"
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
              className="h-7 w-7 hover:bg-muted transition-colors"
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
            className="h-7 w-7 hover:bg-destructive/10 transition-colors"
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

  const enrichStructureWithNames = useCallback(
    (items: LayoutStructureItem[]): LayoutStructureItem[] => {
      if (!items || items.length === 0) return [];
      const enriched: LayoutStructureItem[] = [];
      
      for (const item of items) {
        if (!item.ui_id) {
          item.ui_id = uuidv4();
        }
        
        const itemId = item.entry_mgmt_id || item.entry_desc_id;
        if (!itemId) continue;
        
        const availableItem = availableItems.find(ai => ai.id === itemId);
        if (!availableItem) continue;
        
        const enrichedItem: LayoutStructureItem = {
          ...item,
          ui_id: item.ui_id,
          name: availableItem.name,
          slug: availableItem.slug,
        };
        
        if (item.children && item.children.length > 0) {
          enrichedItem.children = enrichStructureWithNames(item.children);
        }
        enriched.push(enrichedItem);
      }
      return enriched;
    },
    [availableItems]
  );

  useEffect(() => {
    if (!availableItems || availableItems.length === 0) {
      setStructure([]);
      return;
    }
    const enrichedValue = enrichStructureWithNames(value);
    setStructure(enrichedValue);
    
    if (enrichedValue.length !== value.length && onChange) {
      queueMicrotask(() => onChange(enrichedValue));
    }
  }, [value, availableItems, enrichStructureWithNames, onChange]);

  const handleReorder = useCallback(
    (draggedId: string, targetId: string, edge: Edge | null) => {
      if (draggedId === targetId) return;

      const flat = treeHelpers.flatten(structure);
      const draggedIndex = flat.findIndex(i => i.ui_id === draggedId);
      const targetIndex = flat.findIndex(i => i.ui_id === targetId);

      if (draggedIndex < 0 || targetIndex < 0) return;

      const draggedItem = { ...flat[draggedIndex] };
      const targetItem = { ...flat[targetIndex] };

      const descendants: string[] = [];
      const collectDescendants = (parentId: string) => {
        flat.forEach(item => {
          if (item.parentId === parentId) {
            descendants.push(item.ui_id);
            collectDescendants(item.ui_id);
          }
        });
      };
      collectDescendants(draggedId);

      const filtered = flat.filter(
        item => item.ui_id !== draggedId && !descendants.includes(item.ui_id)
      );

      const newTargetIndex = filtered.findIndex(i => i.ui_id === targetId);
      if (newTargetIndex < 0) return;

      let insertIndex = edge === 'bottom' ? newTargetIndex + 1 : newTargetIndex;
      draggedItem.depth = targetItem.depth;
      draggedItem.parentId = targetItem.parentId;

      filtered.splice(insertIndex, 0, draggedItem);

      descendants.forEach(descId => {
        const desc = flat.find(i => i.ui_id === descId);
        if (desc) {
          insertIndex++;
          filtered.splice(insertIndex, 0, { ...desc });
        }
      });

      const newStructure = treeHelpers.unflatten(filtered);
      setStructure(newStructure);
      queueMicrotask(() => onChange(newStructure));
    },
    [structure, onChange]
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
    [onSearch]
  );

  const handleAddItem = useCallback(
    (item: { id: number; name: string; slug?: string }) => {
      const newItem: LayoutStructureItem = {
        ui_id: uuidv4(),
        ...(type === 'entry_desc' ? { entry_desc_id: item.id } : { entry_mgmt_id: item.id }),
        name: item.name,
        slug: item.slug,
      };
      const newStructure = [...structure, newItem];
      setStructure(newStructure);
      queueMicrotask(() => onChange(newStructure));
    },
    [structure, onChange, type]
  );

  const handleRemoveItem = useCallback(
    (uiId: string) => {
      const removeFromTree = (items: LayoutStructureItem[]): LayoutStructureItem[] => {
        return items
          .filter(item => item.ui_id !== uiId)
          .map(item => ({
            ...item,
            children: item.children ? removeFromTree(item.children) : undefined,
          }));
      };
      const newStructure = removeFromTree(structure);
      setStructure(newStructure);
      queueMicrotask(() => onChange(newStructure));
    },
    [structure, onChange]
  );

  const handleToggleCollapse = useCallback((uiId: string) => {
    setCollapsed(prev => {
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
    (uiId: string) => {
      const flat = treeHelpers.flatten(structure);
      const itemIndex = flat.findIndex(i => i.ui_id === uiId);
      if (itemIndex <= 0) return;

      const item = flat[itemIndex];
      
      if (item.depth >= MAX_DEPTH - 1) return;
      
      let prevSibling: FlatItem | null = null;
      for (let i = itemIndex - 1; i >= 0; i--) {
        if (flat[i].parentId === item.parentId && flat[i].depth === item.depth) {
          prevSibling = flat[i];
          break;
        }
      }

      if (!prevSibling) return;

      item.parentId = prevSibling.ui_id;
      item.depth = prevSibling.depth + 1;

      const newStructure = treeHelpers.unflatten(flat);
      setStructure(newStructure);
      queueMicrotask(() => onChange(newStructure));
    },
    [structure, onChange]
  );

  const handleOutdent = useCallback(
    (uiId: string) => {
      const flat = treeHelpers.flatten(structure);
      const itemIndex = flat.findIndex(i => i.ui_id === uiId);
      if (itemIndex < 0) return;

      const item = flat[itemIndex];
      if (!item.parentId || item.depth === 0) return;

      const parentItem = flat.find(i => i.ui_id === item.parentId);
      if (!parentItem) return;

      item.parentId = parentItem.parentId;
      item.depth = parentItem.depth;

      const newStructure = treeHelpers.unflatten(flat);
      setStructure(newStructure);
      queueMicrotask(() => onChange(newStructure));
    },
    [structure, onChange]
  );

  const filteredAvailableItems = availableItems.filter(item =>
    item.name.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <div className="grid grid-cols-2 gap-4 h-full min-h-[400px]">
      {/* Left - Structure */}
      <div className="border rounded-lg p-4 flex flex-col bg-card h-full overflow-hidden">
        <Label className="text-base font-semibold mb-3 shrink-0">Layout Structure</Label>
        <div className="text-xs text-muted-foreground mb-4 space-y-1 bg-muted/30 p-3 rounded-md shrink-0">
          <div className="font-medium">How to organize items:</div>
          <div>• <strong>Drag items</strong> up/down to reorder them</div>
          <div>• <strong>Use →</strong> button to make an item a child of the item above</div>
          <div>• <strong>Use ←</strong> button to move an item up one level</div>
          <div>• <strong>Click chevron (▼/▶)</strong> to collapse/expand children</div>
        </div>
        <div className="flex-1 overflow-y-auto px-1 min-h-0">
          {structure.length === 0 ? (
            <div className="text-muted-foreground text-center py-12 text-sm">
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
      <div className="border rounded-lg p-4 flex flex-col bg-card h-full overflow-hidden">
        <Label className="text-base font-semibold mb-3 shrink-0">Available Items</Label>
        <div className="relative mb-3 shrink-0">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder={`Search ${type === 'entry' ? 'entries' : 'descriptions'}...`}
            value={searchQuery}
            onChange={handleSearchChange}
            disabled={loading}
            className="pl-9"
          />
        </div>
        <div className="flex-1 overflow-y-auto space-y-2 min-h-0 px-1">
          {loading ? (
            <div className="text-center py-8 text-muted-foreground text-sm">Loading...</div>
          ) : filteredAvailableItems.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground text-sm">
              {searchQuery ? 'No items found' : 'No items available'}
            </div>
          ) : (
            filteredAvailableItems.map(item => (
              <Button
                key={item.id}
                variant="outline"
                className="w-full justify-start text-left h-auto py-2.5"
                onClick={() => handleAddItem(item)}
                disabled={loading}
                type="button"
              >
                <div className="flex items-center gap-2 w-full">
                  <div className="h-8 w-8 bg-primary/10 rounded flex items-center justify-center flex-shrink-0">
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
