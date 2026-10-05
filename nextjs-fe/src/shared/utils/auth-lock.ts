const REFRESH_LOCK_KEY = "auth_refresh_lock";
const LOCK_TTL = 5000; // 5 seconds lock duration

export const authLock = {
  async acquire(): Promise<boolean> {
    if (typeof window === "undefined") return false;

    const now = Date.now();
    const lockId = Math.random().toString(36).substring(2);
    const lockData = { id: lockId, expires: now + LOCK_TTL };

    // 1. Check existing valid lock
    const current = this.getLock();
    if (current && current.expires > now) {
      return false;
    }

    // 2. Attempt to set lock
    localStorage.setItem(REFRESH_LOCK_KEY, JSON.stringify(lockData));

    // 3. Wait random delay (10-50ms) to resolve races
    await new Promise((resolve) =>
      setTimeout(resolve, 10 + Math.random() * 40)
    );

    // 4. Verify if we won
    const winner = this.getLock();
    return winner?.id === lockId;
  },

  release(): void {
    if (typeof window === "undefined") return;
    localStorage.removeItem(REFRESH_LOCK_KEY);
  },

  isLocked(): boolean {
    const current = this.getLock();
    return !!current && current.expires > Date.now();
  },

  getLock(): { id: string; expires: number } | null {
    if (typeof window === "undefined") return null;
    try {
      const item = localStorage.getItem(REFRESH_LOCK_KEY);
      return item ? JSON.parse(item) : null;
    } catch {
      return null;
    }
  },
};
