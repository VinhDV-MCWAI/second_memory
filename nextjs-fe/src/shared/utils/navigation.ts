/**
 * Navigation utilities for client-side routing
 * This file provides navigation functions that work with Next.js App Router
 * without causing full page reloads
 */

// Store router instance globally for use in non-React contexts (sagas, interceptors)
let navigateFunction: ((path: string) => void) | null = null;

/**
 * Set the navigation function from a React component
 * This should be called once in the root layout or provider
 */
export const setNavigateFunction = (fn: (path: string) => void) => {
  navigateFunction = fn;
};

/**
 * Navigate to a path using Next.js router (client-side navigation)
 * Falls back to window.location.href if router is not available
 */
export const navigateTo = (path: string) => {
  if (navigateFunction) {
    navigateFunction(path);
  } else {
    // Fallback to window.location if router not initialized
    if (typeof window !== 'undefined') {
      window.location.href = path;
    }
  }
};

/**
 * Clear the navigation function (cleanup)
 */
export const clearNavigateFunction = () => {
  navigateFunction = null;
};
