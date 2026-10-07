import { describe, expect, it } from 'vitest';
import type { LayoutStructureItem } from '@/shared/types/api';
import {
  enrichWithNames,
  flatten,
  indentItem,
  moveItem,
  outdentItem,
  removeItem,
  unflatten,
} from './layout-structure-tree';

const item = (id: string, children?: LayoutStructureItem[]): LayoutStructureItem => ({
  ui_id: id,
  entry_mgmt_id: Number(id.replace(/\D/g, '')) || 1,
  ...(children ? { children } : {}),
});

/** Shape of a tree as nested ids, e.g. `['a', ['b']]`. */
const ids = (items: LayoutStructureItem[]): unknown[] =>
  items.flatMap((i) => (i.children ? [i.ui_id, ids(i.children)] : [i.ui_id]));

describe('layout structure tree', () => {
  const tree = [item('a1', [item('b2'), item('c3')]), item('d4')];

  it('flatten/unflatten round-trips and drops empty children', () => {
    const flat = flatten(tree);
    expect(flat.map((i) => [i.ui_id, i.depth, i.parentId])).toEqual([
      ['a1', 0, null],
      ['b2', 1, 'a1'],
      ['c3', 1, 'a1'],
      ['d4', 0, null],
    ]);
    expect(unflatten(flat)).toEqual(tree);
  });

  it('moveItem moves a subtree next to the target', () => {
    expect(ids(moveItem(tree, 'a1', 'd4', 'bottom')!)).toEqual(['d4', 'a1', ['b2', 'c3']]);
    expect(ids(moveItem(tree, 'c3', 'b2', 'top')!)).toEqual(['a1', ['c3', 'b2'], 'd4']);
    expect(ids(moveItem(tree, 'd4', 'b2', 'bottom')!)).toEqual(['a1', ['b2', 'd4', 'c3']]);
  });

  it('moveItem returns null for a no-op', () => {
    expect(moveItem(tree, 'a1', 'a1', 'top')).toBeNull();
    expect(moveItem(tree, 'x', 'a1', 'top')).toBeNull();
  });

  it('indentItem nests under the previous sibling, outdentItem moves up a level', () => {
    expect(ids(indentItem(tree, 'd4')!)).toEqual(['a1', ['b2', 'c3', 'd4']]);
    expect(ids(indentItem(tree, 'c3')!)).toEqual(['a1', ['b2', ['c3']], 'd4']);
    expect(indentItem(tree, 'a1')).toBeNull();
    expect(indentItem(tree, 'b2')).toBeNull();

    expect(ids(outdentItem(tree, 'b2')!)).toEqual(['a1', ['c3'], 'b2', 'd4']);
    expect(outdentItem(tree, 'a1')).toBeNull();
  });

  it('removeItem removes at any depth', () => {
    expect(ids(removeItem(tree, 'c3'))).toEqual(['a1', ['b2'], 'd4']);
    expect(ids(removeItem(tree, 'a1'))).toEqual(['d4']);
  });

  it('enrichWithNames adds names and drops missing items', () => {
    const value: LayoutStructureItem[] = [
      { ui_id: '', entry_mgmt_id: 1, children: [{ ui_id: 'k', entry_mgmt_id: 9 }] },
      { ui_id: 'z', entry_mgmt_id: 2 },
    ];
    const result = enrichWithNames(value, [{ id: 1, name: 'One', slug: 'one' }]);

    expect(result).toHaveLength(1);
    expect(result[0]).toMatchObject({ name: 'One', slug: 'one', children: [] });
    expect(result[0].ui_id).not.toBe('');
  });
});
