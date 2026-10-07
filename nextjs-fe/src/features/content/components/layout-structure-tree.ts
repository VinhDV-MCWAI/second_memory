import type { Edge } from '@atlaskit/pragmatic-drag-and-drop-hitbox/closest-edge';
import { v4 as uuidv4 } from 'uuid';
import type { LayoutStructureItem } from '@/shared/types/api';

export interface FlatItem extends LayoutStructureItem {
  depth: number;
  parentId: string | null;
}

export interface DragData extends Record<string, unknown> {
  type: 'tree-item';
  itemId: string;
  depth: number;
  parentId: string | null;
}

export const INDENT_WIDTH = 32; // pixels per depth level
export const MAX_DEPTH = 5; // Maximum nesting level

export function flatten(
  items: LayoutStructureItem[],
  parentId: string | null = null,
  depth: number = 0,
): FlatItem[] {
  const flat: FlatItem[] = [];
  items.forEach((item) => {
    flat.push({ ...item, depth, parentId });
    if (item.children?.length) {
      flat.push(...flatten(item.children, item.ui_id, depth + 1));
    }
  });
  return flat;
}

export function unflatten(flatItems: FlatItem[]): LayoutStructureItem[] {
  const map = new Map<string, LayoutStructureItem>();
  const roots: LayoutStructureItem[] = [];

  flatItems.forEach((item) => {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const { depth: _depth, parentId: _parentId, ...data } = item;
    map.set(item.ui_id, { ...data, children: [] });
  });

  flatItems.forEach((item) => {
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
    items.forEach((item) => {
      if (item.children && item.children.length === 0) {
        delete item.children;
      } else if (item.children) {
        cleanup(item.children);
      }
    });
  };
  cleanup(roots);

  return roots;
}

/**
 * Moves `draggedId` (with its subtree) above or below `targetId`, as a sibling of the target.
 * Returns `null` when nothing changes.
 */
export function moveItem(
  structure: LayoutStructureItem[],
  draggedId: string,
  targetId: string,
  edge: Edge | null,
): LayoutStructureItem[] | null {
  if (draggedId === targetId) return null;

  const flat = flatten(structure);
  const draggedIndex = flat.findIndex((i) => i.ui_id === draggedId);
  const targetIndex = flat.findIndex((i) => i.ui_id === targetId);

  if (draggedIndex < 0 || targetIndex < 0) return null;

  const draggedItem = { ...flat[draggedIndex] };
  const targetItem = { ...flat[targetIndex] };

  const descendants: string[] = [];
  const collectDescendants = (parentId: string) => {
    flat.forEach((item) => {
      if (item.parentId === parentId) {
        descendants.push(item.ui_id);
        collectDescendants(item.ui_id);
      }
    });
  };
  collectDescendants(draggedId);

  const filtered = flat.filter(
    (item) => item.ui_id !== draggedId && !descendants.includes(item.ui_id),
  );

  const newTargetIndex = filtered.findIndex((i) => i.ui_id === targetId);
  if (newTargetIndex < 0) return null;

  let insertIndex = edge === 'bottom' ? newTargetIndex + 1 : newTargetIndex;
  draggedItem.depth = targetItem.depth;
  draggedItem.parentId = targetItem.parentId;

  filtered.splice(insertIndex, 0, draggedItem);

  descendants.forEach((descId) => {
    const desc = flat.find((i) => i.ui_id === descId);
    if (desc) {
      insertIndex++;
      filtered.splice(insertIndex, 0, { ...desc });
    }
  });

  return unflatten(filtered);
}

/** Makes the item a child of its previous sibling. Returns `null` when not possible. */
export function indentItem(
  structure: LayoutStructureItem[],
  uiId: string,
): LayoutStructureItem[] | null {
  const flat = flatten(structure);
  const itemIndex = flat.findIndex((i) => i.ui_id === uiId);
  if (itemIndex <= 0) return null;

  const item = flat[itemIndex];

  if (item.depth >= MAX_DEPTH - 1) return null;

  let prevSibling: FlatItem | null = null;
  for (let i = itemIndex - 1; i >= 0; i--) {
    if (flat[i].parentId === item.parentId && flat[i].depth === item.depth) {
      prevSibling = flat[i];
      break;
    }
  }

  if (!prevSibling) return null;

  item.parentId = prevSibling.ui_id;
  item.depth = prevSibling.depth + 1;

  return unflatten(flat);
}

/** Moves the item up one level, next to its parent. Returns `null` when not possible. */
export function outdentItem(
  structure: LayoutStructureItem[],
  uiId: string,
): LayoutStructureItem[] | null {
  const flat = flatten(structure);
  const itemIndex = flat.findIndex((i) => i.ui_id === uiId);
  if (itemIndex < 0) return null;

  const item = flat[itemIndex];
  if (!item.parentId || item.depth === 0) return null;

  const parentItem = flat.find((i) => i.ui_id === item.parentId);
  if (!parentItem) return null;

  item.parentId = parentItem.parentId;
  item.depth = parentItem.depth;

  return unflatten(flat);
}

export function removeItem(items: LayoutStructureItem[], uiId: string): LayoutStructureItem[] {
  return items
    .filter((item) => item.ui_id !== uiId)
    .map((item) => ({
      ...item,
      children: item.children ? removeItem(item.children, uiId) : undefined,
    }));
}

/**
 * Adds `name`/`slug` from the available items and drops entries whose item no longer exists.
 * Items without a `ui_id` get one (assigned in place, as the editor keys rows by it).
 */
export function enrichWithNames(
  items: LayoutStructureItem[],
  availableItems: Array<{ id: number; name: string; slug?: string }>,
): LayoutStructureItem[] {
  if (!items || items.length === 0) return [];
  const enriched: LayoutStructureItem[] = [];

  for (const item of items) {
    if (!item.ui_id) {
      item.ui_id = uuidv4();
    }

    const itemId = item.entry_mgmt_id || item.entry_desc_id;
    if (!itemId) continue;

    const availableItem = availableItems.find((ai) => ai.id === itemId);
    if (!availableItem) continue;

    const enrichedItem: LayoutStructureItem = {
      ...item,
      ui_id: item.ui_id,
      name: availableItem.name,
      slug: availableItem.slug,
    };

    if (item.children && item.children.length > 0) {
      enrichedItem.children = enrichWithNames(item.children, availableItems);
    }
    enriched.push(enrichedItem);
  }
  return enriched;
}
