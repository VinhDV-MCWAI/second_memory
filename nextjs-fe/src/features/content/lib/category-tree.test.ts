import { describe, expect, it } from 'vitest';
import {
  dropTarget,
  flattenTree,
  foldText,
  heightOf,
  matchesQuery,
  selfAndDescendants,
} from './category-tree';

const rows = [
  { id: 1, parent_id: null, name: 'Kỹ năng' },
  { id: 4, parent_id: 2, name: 'Hàng đợi' },
  { id: 2, parent_id: 1, name: 'Laravel' },
  { id: 3, parent_id: null, name: 'Quy trình' },
  { id: 5, parent_id: 1, name: 'Docker' },
];

describe('category tree', () => {
  it('puts every category right after its parent, with level and path', () => {
    expect(
      flattenTree(rows).map((node) => [node.row.id, node.level, node.path.join(' › ')]),
    ).toEqual([
      [1, 0, 'Kỹ năng'],
      [2, 1, 'Kỹ năng › Laravel'],
      [4, 2, 'Kỹ năng › Laravel › Hàng đợi'],
      [5, 1, 'Kỹ năng › Docker'],
      [3, 0, 'Quy trình'],
    ]);
  });

  it('treats a row whose parent is not listed as a top row', () => {
    expect(flattenTree([{ id: 7, parent_id: 99, name: 'Lẻ' }])[0]).toMatchObject({ level: 0 });
  });

  it('finds a branch and how deep it is', () => {
    expect([...selfAndDescendants(rows, 1)].sort()).toEqual([1, 2, 4, 5]);
    expect(heightOf(rows, 1)).toBe(3);
    expect(heightOf(rows, 4)).toBe(1);
  });

  it('matches words of the path without accents', () => {
    expect(foldText('Hàng Đợi')).toBe('hang doi');
    const [, , queue] = flattenTree(rows);
    expect(matchesQuery(queue!, 'laravel hang')).toBe(true);
    expect(matchesQuery(queue!, 'docker')).toBe(false);
  });

  describe('dropTarget', () => {
    // Tree order as the API sends it: 1 › (2 › 4, 5), 3
    const tree = flattenTree(rows).map((node) => node.row);

    it('places a row above, below or inside another, among the siblings without itself', () => {
      expect(dropTarget(tree, 5, 2, 'before', 5)).toEqual({ parent_id: 1, position: 0 });
      expect(dropTarget(tree, 4, 3, 'after', 5)).toEqual({ parent_id: null, position: 2 });
      expect(dropTarget(tree, 3, 5, 'inside', 5)).toEqual({ parent_id: 5, position: 0 });
      expect(dropTarget(tree, 3, 1, 'inside', 5)).toEqual({ parent_id: 1, position: 2 });
    });

    it('refuses its own branch, too many levels and a drop that changes nothing', () => {
      expect(dropTarget(tree, 1, 4, 'inside', 5)).toBeNull();
      expect(dropTarget(tree, 2, 2, 'before', 5)).toBeNull();
      // 2 holds 4: two levels under 5 (level 2) make four, more than 3
      expect(dropTarget(tree, 2, 5, 'inside', 3)).toBeNull();
      expect(dropTarget(tree, 2, 5, 'after', 3)).toEqual({ parent_id: 1, position: 1 });
      // 2 sits right above 5 already
      expect(dropTarget(tree, 2, 5, 'before', 5)).toBeNull();
      expect(dropTarget(tree, 5, 2, 'after', 5)).toBeNull();
    });
  });
});
