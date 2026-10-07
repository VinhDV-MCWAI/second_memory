/**
 * Management models, as the list endpoints return them.
 * Generated from laravel-api/openapi.json (`pnpm gen:api`); do not hand-edit fields here.
 */
import type { components } from '@/shared/types/openapi';

type Schemas = components['schemas'];

/** One node of a `layout_structure` tree. The column is free-form JSON, so the spec only says `unknown[]`. */
export interface LayoutStructureItem {
  ui_id: string;
  entry_desc_id?: number;
  entry_mgmt_id?: number;
  name?: string;
  slug?: string;
  children?: LayoutStructureItem[];
}

type WithLayout<T extends { layout_structure: unknown }> = Omit<T, 'layout_structure'> & {
  layout_structure: LayoutStructureItem[] | null;
};

export type UserMgmt = Schemas['UserMgmtResource'];
export type CategoryMgmt = WithLayout<Schemas['CategoryMgmtResource']>;
export type EntryMgmt = WithLayout<Schemas['EntryMgmtResource']>;
export type EntryDescriptionMgmt = Schemas['EntryDescriptionMgmtResource'];
export type BannerMgmt = Schemas['BannerMgmtResource'];
export type SliderMgmt = Schemas['SliderMgmtResource'];
export type SocialMgmt = Schemas['SocialMgmtResource'];
export type SettingLinkMgmt = Schemas['SettingLinkMgmtResource'];
