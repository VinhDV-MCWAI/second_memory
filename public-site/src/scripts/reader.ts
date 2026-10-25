// Browser side of the reading page (REQ-006, ADR-0024): filters and hidden columns, the current
// category in the kept tree, "On this page" following the scroll, the reading position of a
// read-later article, and real reads. Listeners on the document are added once; the rest is set up
// for every page the router shows (astro:page-load) and torn down before the next swap.
import { READER_HIDDEN_KEY } from '@/lib/reader';
import { ROUTES, type ViewKind } from '@/lib/routes';
import { api, maybeSignedIn, savedItems } from '@/scripts/user-api';

/** A heading counts as reached once its top passes this far below the top of the window */
const HEADING_OFFSET_PX = 96;
/** Real read of an article: shown min(15 s, 30% of its reading time) and scrolled a quarter of it */
const READ_MAX_MS = 15_000;
const READ_SHARE = 0.3;
const WORDS_PER_MINUTE = 200;
const MINUTE_MS = 60_000;
const SCROLL_SHARE = 0.25;
/** A category is viewed once its page was shown this long */
const CATEGORY_VIEW_MS = 10_000;
const TICK_MS = 1_000;
/** The reading position is sent at most this often */
const POSITION_EVERY_MS = 10_000;
const SAVED_READ_LATER = 'read_later';
const POSITION_LEVEL = 2;

const COLUMNS = ['tree', 'list', 'toc'] as const;
type Column = (typeof COLUMNS)[number];

// Accents and case do not matter in the filters ("hang doi" finds "Hàng đợi")
const plain = (text: string) =>
  text.normalize('NFD').replace(/\p{M}/gu, '').replace(/đ/gi, 'd').toLowerCase().trim();

// ---------- Filters: one box per column, items carry data-filter-text ----------

function filter(input: HTMLInputElement): void {
  const scope = input.closest('[data-filter-scope]');
  if (!scope) return;
  const words = plain(input.value);
  const items = [...scope.querySelectorAll<HTMLElement>('[data-filter-item]')].reverse();
  // Children come before their parents here, so a parent stays when one of them matches
  for (const item of items) {
    const own = plain(item.dataset.filterText ?? '').includes(words);
    item.hidden = !own && !item.querySelector('[data-filter-item]:not([hidden])');
    if (words && !item.hidden) {
      item.querySelector(':scope > details')?.setAttribute('open', '');
    }
  }
}

document.addEventListener('input', (event) => {
  if (event.target instanceof HTMLInputElement && event.target.matches('[data-filter]')) {
    filter(event.target);
  }
});

// ---------- Hidden columns, remembered in this browser ----------

const reader = () => document.querySelector<HTMLElement>('[data-reader]');

function hiddenColumns(): Column[] {
  const names = (reader()?.dataset.hidden ?? '').split(' ');
  return COLUMNS.filter((column) => names.includes(column));
}

function showButtons(): void {
  const hidden = hiddenColumns();
  for (const button of document.querySelectorAll<HTMLButtonElement>('[data-collapse]')) {
    const column = button.dataset.collapse as Column;
    const isHidden = hidden.includes(column);
    button.setAttribute('aria-expanded', String(!isHidden));
    // The arrow points where the column goes: tree and list to the left, contents to the right
    button.textContent = (column === 'toc') === isHidden ? '«' : '»';
  }
}

document.addEventListener('click', (event) => {
  const button = (event.target as Element).closest<HTMLButtonElement>('[data-collapse]');
  const page = reader();
  if (!button || !page) return;
  const column = button.dataset.collapse as Column;
  const hidden = hiddenColumns();
  const next = hidden.includes(column)
    ? hidden.filter((name) => name !== column)
    : [...hidden, column];
  page.dataset.hidden = next.join(' ');
  try {
    localStorage.setItem(READER_HIDDEN_KEY, JSON.stringify(next));
  } catch {
    // Storage blocked: the column is hidden on this page only
  }
  showButtons();
});

// ---------- The current category in the tree (column 1 is kept across pages) ----------

function markCategory(): void {
  const current = reader()?.dataset.currentCategory ?? '';
  for (const link of document.querySelectorAll<HTMLAnchorElement>('a[data-category]')) {
    if (link.dataset.category === current) {
      link.setAttribute('aria-current', 'page');
      for (
        let up = link.closest('details');
        up;
        up = up.parentElement?.closest('details') ?? null
      ) {
        up.open = true;
      }
    } else {
      link.removeAttribute('aria-current');
    }
  }
}

// ---------- Per page ----------

/** Undoes what the current page set up */
let tearDown: (() => void)[] = [];

function every(ms: number, run: () => void): void {
  const timer = setInterval(run, ms);
  tearDown.push(() => clearInterval(timer));
}

function onScroll(run: () => void): void {
  let queued = false;
  const listener = () => {
    if (queued) return;
    queued = true;
    requestAnimationFrame(() => {
      queued = false;
      run();
    });
  };
  addEventListener('scroll', listener, { passive: true });
  tearDown.push(() => removeEventListener('scroll', listener));
}

function reportRead(kind: ViewKind, slug: string): void {
  navigator.sendBeacon(ROUTES.view(kind, slug), new Blob(['{}'], { type: 'application/json' }));
}

/** Milliseconds the tab was shown since the page opened, counted in ticks */
function shownTime(onTick: (shown: number) => boolean): void {
  let shown = 0;
  let done = false;
  every(TICK_MS, () => {
    if (done || document.visibilityState !== 'visible') return;
    shown += TICK_MS;
    done = onTick(shown);
  });
}

function countCategoryView(slug: string): void {
  shownTime((shown) => {
    if (shown < CATEGORY_VIEW_MS) return false;
    reportRead('categories', slug);
    return true;
  });
}

function countArticleRead(slug: string, body: HTMLElement): void {
  const words = (body.textContent ?? '').split(/\s+/).filter(Boolean).length;
  const needMs = Math.min(READ_MAX_MS, (words / WORDS_PER_MINUTE) * MINUTE_MS * READ_SHARE);
  let scrolled = false;
  const checkScroll = () => {
    const height = body.offsetHeight;
    const most = document.documentElement.scrollHeight - innerHeight;
    // A short article (or one at the end of the page) needs no more scrolling than there is
    scrolled ||= scrollY >= Math.min(height * SCROLL_SHARE, most - 1);
  };
  checkScroll();
  onScroll(checkScroll);
  shownTime((shown) => {
    if (shown < needMs || !scrolled) return false;
    reportRead('articles', slug);
    return true;
  });
}

interface Heading {
  id: string;
  level: number;
  element: HTMLElement;
  link: HTMLAnchorElement;
}

function headingsOfPage(): Heading[] {
  return [...document.querySelectorAll<HTMLAnchorElement>('a[data-toc]')].flatMap((link) => {
    const element = document.getElementById(link.dataset.toc ?? '');
    return element ? [{ id: element.id, level: Number(link.dataset.level), element, link }] : [];
  });
}

/** The last heading that went past the top of the window, or none above the first */
function reached(headings: Heading[]): Heading | undefined {
  return headings.findLast(
    ({ element }) => element.getBoundingClientRect().top <= HEADING_OFFSET_PX,
  );
}

function followContents(headings: Heading[]): void {
  const column = document.querySelector<HTMLElement>('[data-column="toc"]');
  let current: Heading | undefined;
  const update = () => {
    const next = reached(headings) ?? headings[0];
    if (next === current) return;
    current?.link.removeAttribute('aria-current');
    next?.link.setAttribute('aria-current', 'true');
    current = next;
    // Keep the lit heading inside the column without moving the page
    if (next && column) {
      const { offsetTop, offsetHeight } = next.link;
      if (
        offsetTop < column.scrollTop ||
        offsetTop + offsetHeight > column.scrollTop + column.clientHeight
      ) {
        column.scrollTop = offsetTop - column.clientHeight / 2;
      }
    }
  };
  update();
  onScroll(update);
}

// The `##` section the reader reached, kept for a read-later article (ADR-0024): sent when it
// changes, at most every 10 s, and once more when leaving the page
async function keepPosition(key: string, headings: Heading[]): Promise<void> {
  if (!maybeSignedIn()) return;
  const sections = headings.filter(({ level }) => level === POSITION_LEVEL);
  const page = reader();
  const items = await savedItems().catch(() => []);
  // Not saved for later, or the router already showed another page meanwhile
  if (
    !items.some((item) => item.kind === SAVED_READ_LATER && item.key === key) ||
    reader() !== page
  ) {
    return;
  }

  const sectionNow = () => reached(sections)?.id ?? null;
  let sent = sectionNow();
  let pending: string | null = sent;
  let lastAt = 0;
  let timer: ReturnType<typeof setTimeout> | undefined;
  const send = (keepalive = false) => {
    clearTimeout(timer);
    timer = undefined;
    if (pending === sent) return;
    sent = pending;
    lastAt = Date.now();
    void api('POST', 'user/saved/position', { article: key, anchor: sent }, { keepalive }).catch(
      () => undefined,
    );
  };
  onScroll(() => {
    pending = sectionNow();
    if (pending === sent || timer) return;
    timer = setTimeout(send, Math.max(0, lastAt + POSITION_EVERY_MS - Date.now()));
  });
  const leave = () => send(true);
  addEventListener('pagehide', leave);
  tearDown.push(() => {
    removeEventListener('pagehide', leave);
    send();
  });
}

// A section asked for in the address (from the saved list) that the article no longer has
function warnMissingSection(): void {
  const notice = document.querySelector<HTMLElement>('[data-missing-section]');
  const id = decodeURIComponent(location.hash.slice(1));
  if (notice && id && !document.getElementById(id)) {
    notice.hidden = false;
  }
}

document.addEventListener('astro:page-load', () => {
  showButtons();
  markCategory();
  const category = document.querySelector<HTMLElement>('[data-read-category]');
  if (category) {
    countCategoryView(category.dataset.readCategory ?? '');
  }
  const body = document.querySelector<HTMLElement>('[data-article-body]');
  const article = body?.closest<HTMLElement>('[data-read-article]');
  if (body && article) {
    const headings = headingsOfPage();
    countArticleRead(article.dataset.readArticle ?? '', body);
    followContents(headings);
    void keepPosition(article.dataset.articleKey ?? '', headings);
    warnMissingSection();
  }
});

// Column 1 is moved into the next page; browsers without moveBefore() reset its scroll on the way
let treeScroll = 0;
const treeColumn = () => document.querySelector<HTMLElement>('[data-column="tree"]');

document.addEventListener('astro:before-swap', () => {
  for (const undo of tearDown) undo();
  tearDown = [];
  treeScroll = treeColumn()?.scrollTop ?? 0;
});

document.addEventListener('astro:after-swap', () => {
  const tree = treeColumn();
  if (tree) tree.scrollTop = treeScroll;
});
