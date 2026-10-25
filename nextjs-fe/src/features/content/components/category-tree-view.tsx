'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import {
  DndContext,
  DragOverlay,
  PointerSensor,
  pointerWithin,
  useDraggable,
  useDroppable,
  useSensor,
  useSensors,
  type DragMoveEvent,
} from '@dnd-kit/core';
import { ChevronDown, ChevronRight, Edit, GripVertical, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { LoadingOverlay } from '@/components/ui/loading';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { ResourceViewActions } from '@/components/common/resource-list-page';
import { MoveButtons } from '@/features/content/components/move-buttons';
import { useCategoryTree } from '@/features/content/hooks/use-content-queries';
import { useMoveRow } from '@/features/content/hooks/use-move-row';
import {
  dropTarget,
  flattenTree,
  matchesQuery,
  type CategoryNode,
  type DropTarget,
  type DropZone,
} from '@/features/content/lib/category-tree';
import { API_ENDPOINTS } from '@/shared/api';
import { usePermissions } from '@/shared/hooks/use-permissions';
import { ValidationRules } from '@/shared/validation/validation-rules';
import { featureOfEndpoint, permissionName } from '@/shared/utils/permissions';
import { cn } from '@/shared/utils';
import type { ContentCategory } from '@/shared/types/models';

/** Indent of one level, in pixels (levels are dynamic, so not a Tailwind class) */
const LEVEL_INDENT = 20;
/** Share of a row's height at its top and bottom that drops above / below it; the middle drops inside */
const EDGE_ZONE = 0.25;
/** Pixels the pointer moves before a drag starts, so a click on the handle stays a click */
const DRAG_DISTANCE = 5;

const ENDPOINT = API_ENDPOINTS.CONTENT.CATEGORY;

interface Hover {
  overId: number;
  zone: DropZone;
  /** Null: the drop is not allowed or changes nothing */
  target: DropTarget | null;
}

/**
 * The categories screen as a tree (ADR-0023): a branch folds and unfolds, a search shows the
 * matches with the categories above them, and a row dragged by its handle drops above, below or
 * inside another, with the same rules as the form (no loop, CATEGORY_DEPTH_MAX levels).
 */
export function CategoryTreeView({ edit, remove }: ResourceViewActions<ContentCategory>) {
  const t = useTranslations('content');
  const tFields = useTranslations('fields');
  const tCommon = useTranslations('common');
  const { can } = usePermissions();
  const canMove = can(permissionName(featureOfEndpoint(ENDPOINT), 'move'));
  const { data: rows, loading } = useCategoryTree();
  const move = useMoveRow(ENDPOINT);
  const [collapsed, setCollapsed] = useState<Set<number>>(new Set());
  const [query, setQuery] = useState('');
  const [draggingId, setDraggingId] = useState<number | null>(null);
  const [hover, setHover] = useState<Hover | null>(null);
  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: DRAG_DISTANCE } }),
  );

  const nodes = flattenTree(rows);
  const parents = new Set(rows.map((row) => row.parent_id));
  const searching = query.trim() !== '';
  const shown = searching ? withAncestors(nodes, query) : nodes.filter(visible(collapsed));
  const siblingsOf = (row: ContentCategory) => rows.filter((r) => r.parent_id === row.parent_id);
  const dragging = rows.find((row) => row.id === draggingId);

  const toggle = (id: number) =>
    setCollapsed((current) => {
      const next = new Set(current);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });

  const onDragMove = ({ active, over, activatorEvent, delta }: DragMoveEvent) => {
    if (!over) {
      setHover(null);
      return;
    }
    const overId = Number(over.id);
    const pointerY = (activatorEvent as PointerEvent).clientY + delta.y;
    const share = (pointerY - over.rect.top) / over.rect.height;
    let zone: DropZone = share < EDGE_ZONE ? 'before' : share > 1 - EDGE_ZONE ? 'after' : 'inside';
    let targetId = overId;
    // Below an open branch is above its first category, where the line shows
    const firstChild = rows.find((row) => row.parent_id === overId);
    if (zone === 'after' && firstChild && !collapsed.has(overId)) {
      zone = 'before';
      targetId = firstChild.id;
    }
    const target = dropTarget(
      rows,
      Number(active.id),
      targetId,
      zone,
      ValidationRules.CATEGORY_DEPTH_MAX,
    );
    setHover({ overId, zone: targetId === overId ? zone : 'after', target });
  };

  const endDrag = () => {
    setDraggingId(null);
    setHover(null);
  };

  const onDragEnd = () => {
    const target = hover?.target;
    if (draggingId !== null && target) {
      move.mutate({ id: draggingId, ...target });
      // A drop into a folded branch opens it, so the row does not vanish
      if (target.parent_id !== null && collapsed.has(target.parent_id)) {
        toggle(target.parent_id);
      }
    }
    endDrag();
  };

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap items-center gap-2">
        <Input
          aria-label={t('searchCategories')}
          placeholder={t('searchCategories')}
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          className="max-w-xs"
        />
        <Button type="button" variant="outline" size="sm" onClick={() => setCollapsed(new Set())}>
          {t('expandAll')}
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={() => setCollapsed(new Set([...parents].filter((id) => id !== null)))}
        >
          {t('collapseAll')}
        </Button>
        {canMove && (
          <span className="text-sm text-muted-foreground">
            {t(searching ? 'dragNotWhileSearching' : 'dragHint')}
          </span>
        )}
      </div>

      <DndContext
        sensors={sensors}
        collisionDetection={pointerWithin}
        onDragStart={({ active }) => setDraggingId(Number(active.id))}
        onDragMove={onDragMove}
        onDragEnd={onDragEnd}
        onDragCancel={endDrag}
      >
        <div className="relative min-h-[100px] overflow-hidden rounded-lg border border-slate-200 dark:border-slate-800">
          {loading && <LoadingOverlay variant="absolute" />}
          <div className="flex items-center gap-2 bg-slate-50 px-2 py-2 text-sm font-medium text-slate-600 dark:bg-slate-900 dark:text-slate-400">
            <span className="flex-1 pl-14">{tFields('name')}</span>
            <span className="w-20 text-right">{tFields('articleCount')}</span>
            <span className="w-24">{tFields('visibility')}</span>
            <span className="w-20">{tFields('order')}</span>
            <span className="w-24 text-right">{tCommon('actions')}</span>
          </div>
          <div role="tree" aria-label={tFields('name')}>
            {shown.length === 0 && !loading ? (
              <p className="py-12 text-center text-slate-600 dark:text-slate-400">
                {t(searching ? 'noCategoryMatches' : 'noCategoriesYet')}
              </p>
            ) : (
              shown.map((node) => {
                const siblings = siblingsOf(node.row);
                return (
                  <TreeRow
                    key={node.row.id}
                    node={node}
                    hasChildren={parents.has(node.row.id)}
                    open={searching || !collapsed.has(node.row.id)}
                    onToggle={() => toggle(node.row.id)}
                    draggable={canMove && !searching && !move.isPending}
                    dimmed={draggingId === node.row.id}
                    hover={hover?.overId === node.row.id ? hover : null}
                    first={siblings[0]?.id === node.row.id}
                    last={siblings[siblings.length - 1]?.id === node.row.id}
                    edit={edit}
                    remove={remove}
                  />
                );
              })
            )}
          </div>
        </div>
        <DragOverlay dropAnimation={null}>
          {dragging && (
            <div className="rounded-md border bg-background px-3 py-1.5 text-sm shadow-lg">
              {dragging.name}
            </div>
          )}
        </DragOverlay>
      </DndContext>
    </div>
  );
}

/** Rows whose categories above are all open. */
const visible = (collapsed: Set<number>) => {
  const hidden = new Set<number>();
  return (node: CategoryNode<ContentCategory>) => {
    const { id, parent_id: parentId } = node.row;
    // Tree order: a parent is decided before its children
    if (parentId !== null && (hidden.has(parentId) || collapsed.has(parentId))) {
      hidden.add(id);
      return false;
    }
    return true;
  };
};

/** The matches of $query and the categories above them, in tree order. */
function withAncestors(nodes: CategoryNode<ContentCategory>[], query: string) {
  const byId = new Map(nodes.map((node) => [node.row.id, node.row]));
  const keep = new Set<number>();
  for (const node of nodes.filter((n) => matchesQuery(n, query))) {
    for (let row = byId.get(node.row.id); row; row = byId.get(row.parent_id ?? -1)) {
      keep.add(row.id);
    }
  }
  return nodes.filter((node) => keep.has(node.row.id));
}

interface TreeRowProps extends ResourceViewActions<ContentCategory> {
  node: CategoryNode<ContentCategory>;
  hasChildren: boolean;
  open: boolean;
  onToggle: () => void;
  draggable: boolean;
  dimmed: boolean;
  hover: Hover | null;
  first: boolean;
  last: boolean;
}

function TreeRow({
  node,
  hasChildren,
  open,
  onToggle,
  draggable,
  dimmed,
  hover,
  first,
  last,
  edit,
  remove,
}: TreeRowProps) {
  const t = useTranslations('content');
  const tCommon = useTranslations('common');
  const { row, level } = node;
  const { setNodeRef, listeners, attributes } = useDraggable({ id: row.id, disabled: !draggable });
  const { setNodeRef: setDropRef } = useDroppable({ id: row.id, disabled: !draggable });
  const Toggle = open ? ChevronDown : ChevronRight;
  const allowed = hover?.target != null;

  return (
    <div
      ref={setDropRef}
      role="treeitem"
      aria-label={row.name}
      aria-level={level + 1}
      aria-expanded={hasChildren ? open : undefined}
      aria-selected={false}
      className={cn(
        'flex items-center gap-2 border-t border-slate-200 px-2 py-1.5 text-sm dark:border-slate-800',
        'hover:bg-slate-50 dark:hover:bg-slate-800',
        dimmed && 'opacity-40',
        hover && allowed && hover.zone === 'before' && 'shadow-[inset_0_2px_0_0_var(--primary)]',
        hover && allowed && hover.zone === 'after' && 'shadow-[inset_0_-2px_0_0_var(--primary)]',
        hover && allowed && hover.zone === 'inside' && 'bg-primary/10',
        hover && !allowed && 'cursor-not-allowed',
      )}
    >
      <div className="flex flex-1 items-center gap-1" style={{ paddingLeft: level * LEVEL_INDENT }}>
        {draggable ? (
          <button
            type="button"
            ref={setNodeRef}
            {...listeners}
            {...attributes}
            aria-label={t('dragNamed', { name: row.name })}
            className="cursor-grab touch-none rounded p-0.5 text-muted-foreground hover:text-foreground"
          >
            <GripVertical className="h-4 w-4" />
          </button>
        ) : (
          <span className="w-5" />
        )}
        {hasChildren ? (
          <button
            type="button"
            onClick={onToggle}
            aria-label={t(open ? 'collapseNamed' : 'expandNamed', { name: row.name })}
            className="rounded p-0.5 text-muted-foreground hover:text-foreground"
          >
            <Toggle className="h-4 w-4" />
          </button>
        ) : (
          <span className="w-5" />
        )}
        <span className={cn(level === 0 && 'font-semibold')}>{row.name}</span>
      </div>
      <span className="w-20 text-right tabular-nums">{row.article_count}</span>
      <span className="w-24">
        <StatusBadge active={row.is_public}>
          {row.is_public ? t('public') : t('private')}
        </StatusBadge>
      </span>
      <span className="w-20">
        <MoveButtons endpoint={ENDPOINT} id={row.id} first={first} last={last} />
      </span>
      <span className="w-24 space-x-1 text-right">
        {edit && (
          <Button variant="ghost" size="sm" aria-label={tCommon('edit')} onClick={() => edit(row)}>
            <Edit className="h-4 w-4" />
          </Button>
        )}
        {remove && (
          <Button
            variant="ghost"
            size="sm"
            aria-label={tCommon('delete')}
            onClick={() => remove(row.id)}
            className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
          >
            <Trash2 className="h-4 w-4" />
          </Button>
        )}
      </span>
    </div>
  );
}
