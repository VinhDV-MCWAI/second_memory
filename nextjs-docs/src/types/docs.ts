export interface Category {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  rank_order: number;
  layout_structure?: any;
}

export interface Entry {
  id: number;
  name: string;
  slug: string;
  rank_order: number;
  layout_structure?: any;
}

export interface Description {
  id: number;
  title: string;
  summary: string;
  article: string;
  rank_order: number;
}

export interface EntryDetail {
  id: number;
  name: string;
  slug: string;
  categories: Category[];
  descriptions: Description[];
  layout_structure?: any;
}

export interface SearchResult {
  categories: Category[];
  entries: Entry[];
  descriptions: Array<{
    id: number;
    title: string;
    summary: string;
    article: string;
    entry: Entry;
  }>;
}

export interface ApiResponse<T> {
  status: boolean;
  message: string;
  data: T;
}
