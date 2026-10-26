// Same limits as the API (SearchConst: QUERY_MIN, QUERY_MAX, WORD_PATTERN): a query it would refuse
// is answered without calling it. Used by the search page and the header's search box.
export const QUERY_MIN = 2;
export const QUERY_MAX = 100;
const HAS_WORD = /[\p{L}\p{N}]/u;

export const isSearchable = (query: string): boolean =>
  query.length >= QUERY_MIN && HAS_WORD.test(query);
