// The header's search box (REQ-006): results come once typing pauses, without pressing Enter. The
// search page renders them on the server; this script fetches that page and takes its results
// part: on the search page it replaces the list in place, elsewhere it shows it in a panel under
// the box. Enter still opens the search page, and without JavaScript the box is a plain form.
// Listeners sit on the document, so they keep working after the reading page's router swaps the
// header (ADR-0024).
import { ROUTES } from '@/lib/routes';
import { isSearchable } from '@/lib/search-query';

/** Typing has paused for this long */
const PAUSE_MS = 350;
const PANEL_ID = 'header-search-panel';

let timer: ReturnType<typeof setTimeout> | undefined;
let running: AbortController | undefined;

const box = () => document.querySelector<HTMLInputElement>('[data-header-search] input');
const panel = () => document.getElementById(PANEL_ID);

function closePanel(): void {
  panel()?.remove();
  box()?.setAttribute('aria-expanded', 'false');
}

function showPanel(input: HTMLInputElement, results: string, query: string): void {
  let shown = panel();
  if (!shown) {
    shown = document.createElement('div');
    shown.id = PANEL_ID;
    shown.className = 'search-panel';
    input.after(shown);
    input.setAttribute('aria-controls', PANEL_ID);
  }
  input.setAttribute('aria-expanded', 'true');
  const all = `${ROUTES.SEARCH}?q=${encodeURIComponent(query)}`;
  shown.innerHTML = `${results}<a href="${all}" data-astro-reload class="search-panel-all">All results →</a>`;
}

async function search(input: HTMLInputElement): Promise<void> {
  const query = input.value.trim();
  const inPlace = document.querySelector<HTMLElement>('[data-search-results]');
  if (!isSearchable(query)) {
    if (!inPlace) closePanel();
    return;
  }
  running?.abort();
  running = new AbortController();
  const url = `${ROUTES.SEARCH}?q=${encodeURIComponent(query)}`;
  let page: Document;
  try {
    const response = await fetch(url, { signal: running.signal });
    page = new DOMParser().parseFromString(await response.text(), 'text/html');
  } catch {
    // Aborted by newer typing, or offline: Enter still opens the search page
    return;
  }
  // The server's own HTML (the same page Enter would open)
  const results = page.querySelector('[data-search-results]')?.innerHTML ?? '';
  if (inPlace) {
    inPlace.innerHTML = results;
    document.title = page.title;
    history.replaceState(history.state, '', url);
  } else if (document.activeElement === input) {
    showPanel(input, results, query);
  }
}

document.addEventListener('input', (event) => {
  const input = event.target;
  if (!(input instanceof HTMLInputElement) || !input.closest('[data-header-search]')) return;
  clearTimeout(timer);
  timer = setTimeout(() => void search(input), PAUSE_MS);
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && panel()) {
    closePanel();
    box()?.focus();
  }
});

// A click outside the box and its panel closes the panel
document.addEventListener('click', (event) => {
  if (panel() && !(event.target as Element).closest('[data-header-search]')) closePanel();
});

// Back in the box after closing the panel: show the results again (the search page has them)
document.addEventListener('focusin', (event) => {
  const input = event.target;
  if (
    input instanceof HTMLInputElement &&
    input.closest('[data-header-search]') &&
    input.value &&
    !panel() &&
    !document.querySelector('[data-search-results]')
  ) {
    clearTimeout(timer);
    void search(input);
  }
});
