/**
 * Background tasks this tab started and has not seen finish yet (ADR-0014). Kept in sessionStorage so a
 * reload still asks the API for their status: the live event is lost if it arrives while no page listens.
 */
const STORAGE_KEY = 'second-memory.pending-background-tasks';

function read(): string[] {
  if (typeof window === 'undefined') return [];
  try {
    const parsed: unknown = JSON.parse(window.sessionStorage.getItem(STORAGE_KEY) ?? '[]');
    return Array.isArray(parsed) ? parsed.filter((id): id is string => typeof id === 'string') : [];
  } catch {
    return [];
  }
}

function write(ids: string[]): void {
  if (typeof window === 'undefined') return;
  try {
    window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
  } catch {
    // Storage full or blocked: the live event still arrives while the page stays open
  }
}

export const pendingTasks = {
  list: read,
  add(id: string): void {
    const ids = read();
    if (!ids.includes(id)) write([...ids, id]);
  },
  /** True when the id was pending, so a result is announced once even if both the event and the status check see it. */
  remove(id: string): boolean {
    const ids = read();
    if (!ids.includes(id)) return false;
    write(ids.filter((pending) => pending !== id));
    return true;
  },
};
