// Shared by the reading page's layout and its script (ADR-0024)

/** localStorage key of the columns the visitor hid: a JSON list of 'tree' | 'list' | 'toc' */
export const READER_HIDDEN_KEY = 'reader-hidden';

/** Articles column 2 lists for a category: the API's page limit (ContentConst::PUBLIC_PER_PAGE_MAX) */
export const READER_LIST_SIZE = 100;
