// Scripts run once, but the reading page's router (ADR-0024) swaps the page under them: work that
// belongs to the page runs now and again after every swap.
export function onEveryPage(run: () => void): void {
  run();
  document.addEventListener('astro:after-swap', run);
}
