// The flat category list of the API (tree order, each with its parent's slug) as the nested tree
// of the reading page's first column (ADR-0024).
import type { TreeNode } from '@/components/CategoryTree.astro';
import type { PublicCategoryNode } from '@/lib/public-api';

export function buildTree(categories: PublicCategoryNode[]): TreeNode[] {
  const nodes = new Map<string, TreeNode>();
  const roots: TreeNode[] = [];
  for (const { name, slug, article_count, parent_slug } of categories) {
    const node: TreeNode = { name, slug, article_count, children: [] };
    nodes.set(slug, node);
    const parent = parent_slug === null ? undefined : nodes.get(parent_slug);
    (parent ? parent.children : roots).push(node);
  }
  return roots;
}

/** Slugs from the top down to a category; empty when it is not in the list */
export function pathTo(categories: PublicCategoryNode[], slug: string | null): string[] {
  const parents = new Map(categories.map((category) => [category.slug, category.parent_slug]));
  const path: string[] = [];
  for (let at: string | null | undefined = slug; at && parents.has(at); at = parents.get(at)) {
    path.unshift(at);
  }
  return path;
}
