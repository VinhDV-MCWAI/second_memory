/** Node of a category/entry layout tree built in the admin editor. */
export interface LayoutNode {
  ui_id?: string;
  entry_mgmt_id?: number | string;
  entry_desc_id?: number | string;
  children?: LayoutNode[];
}

/** `layout_structure` as the API returns it: a JSON array, or that array serialised as a string. */
export type RawLayoutStructure = LayoutNode[] | string | null;

export function parseLayoutStructure(raw: RawLayoutStructure | undefined): LayoutNode[] | null {
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
