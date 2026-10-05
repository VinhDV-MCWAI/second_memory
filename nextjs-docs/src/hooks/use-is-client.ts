import { useSyncExternalStore } from 'react';

const subscribe = () => () => {};

/**
 * false during SSR and hydration, true afterwards — without a setState-in-effect
 * round trip.
 */
export function useIsClient(): boolean {
  return useSyncExternalStore(
    subscribe,
    () => true,
    () => false,
  );
}
