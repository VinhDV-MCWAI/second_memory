/** A category as the tree helpers need it (a row of `content-category/list`). */
export interface CategoryRow {
  id: number;
  parent_id: number | null;
  name: string;
}

/** A category placed in the tree: its level (0 at the top) and the names from the top down. */
export interface CategoryNode<T extends CategoryRow = CategoryRow> {
  row: T;
  level: number;
  path: string[];
}

/**
 * Rows in tree order (each category right after its parent, siblings in the order given) with
 * their level and path. A row whose parent is missing from the list counts as a top row.
 */
export function flattenTree<T extends CategoryRow>(rows: T[]): CategoryNode<T>[] {
  const ids = new Set(rows.map((row) => row.id));
  const childrenOf = new Map<number | null, T[]>();
  for (const row of rows) {
    const parent = row.parent_id !== null && ids.has(row.parent_id) ? row.parent_id : null;
    childrenOf.set(parent, [...(childrenOf.get(parent) ?? []), row]);
  }

  const out: CategoryNode<T>[] = [];
  const visit = (parent: number | null, level: number, path: string[]) => {
    for (const row of childrenOf.get(parent) ?? []) {
      const own = [...path, row.name];
      out.push({ row, level, path: own });
      visit(row.id, level + 1, own);
    }
  };
  visit(null, 0, []);
  return out;
}

/** Ids of $id and every category below it, at any level. */
export function selfAndDescendants(rows: CategoryRow[], id: number): Set<number> {
  const found = new Set([id]);
  let grew = true;
  while (grew) {
    grew = false;
    for (const row of rows) {
      if (row.parent_id !== null && found.has(row.parent_id) && !found.has(row.id)) {
        found.add(row.id);
        grew = true;
      }
    }
  }
  return found;
}

/** Levels of the subtree under $id, $id itself being 1: what a move takes along. */
export function heightOf(rows: CategoryRow[], id: number): number {
  const below = rows.filter((row) => row.parent_id === id);
  return 1 + Math.max(0, ...below.map((row) => heightOf(rows, row.id)));
}

/** Lower case without accents, so "hang doi" finds "Hàng đợi". */
export function foldText(text: string): string {
  return text
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .replace(/đ/g, 'd')
    .replace(/Đ/g, 'd')
    .toLowerCase();
}

/** Whether every word of $query is in the node's path (accents and case ignored). */
export function matchesQuery(node: CategoryNode, query: string): boolean {
  const words = foldText(query).split(/\s+/).filter(Boolean);
  const text = foldText(node.path.join(' '));
  return words.every((word) => text.includes(word));
}
