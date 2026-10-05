/**
 * ============================================================================
 * ROLE WIZARD CONSTANTS & TYPES
 * ============================================================================
 * Constants, types and interfaces for Role Wizard feature
 */

import { HTTP_METHODS } from './constant';

// Re-export HTTP_METHODS for convenience
export { HTTP_METHODS };

// ============================================================================
// HTTP METHOD LABELS & COLORS
// ============================================================================

export const HTTP_METHOD_LABELS: Record<string, { label: string; color: string }> = {
  [HTTP_METHODS.GET]: { label: 'GET', color: 'bg-blue-100 text-blue-800' },
  [HTTP_METHODS.POST]: { label: 'POST', color: 'bg-green-100 text-green-800' },
  [HTTP_METHODS.PUT]: { label: 'PUT', color: 'bg-orange-100 text-orange-800' },
  [HTTP_METHODS.PATCH]: { label: 'PATCH', color: 'bg-purple-100 text-purple-800' },
  [HTTP_METHODS.DELETE]: { label: 'DELETE', color: 'bg-red-100 text-red-800' },
};

// Map API type numbers to HTTP methods
export const API_TYPE_TO_METHOD: Record<number, string> = {
  1: HTTP_METHODS.GET,
  2: HTTP_METHODS.POST,
  3: HTTP_METHODS.PUT,
  4: HTTP_METHODS.PATCH,
  5: HTTP_METHODS.DELETE,
};

export const METHOD_TO_API_TYPE: Record<string, number> = {
  [HTTP_METHODS.GET]: 1,
  [HTTP_METHODS.POST]: 2,
  [HTTP_METHODS.PUT]: 3,
  [HTTP_METHODS.PATCH]: 4,
  [HTTP_METHODS.DELETE]: 5,
};

// ============================================================================
// WIZARD DIMENSIONS & LAYOUT
// ============================================================================

export const ROLE_WIZARD_LAYOUT = {
  // Step 2 dual layout percentages
  LEFT_PANEL_WIDTH: '30%',   // Features list
  RIGHT_PANEL_WIDTH: '70%',  // APIs list
  
  // Container dimensions
  MIN_HEIGHT: '400px',
  CONTENT_HEIGHT: 'h-[500px]',
  MAX_WIDTH: 'max-w-4xl',
  MAX_HEIGHT: 'max-h-[90vh]',
  
  // Step heights
  REVIEW_CONTENT_MAX_HEIGHT: 'max-h-[600px]',
} as const;

// ============================================================================
// WIZARD STATE
// ============================================================================

export const WIZARD_STEPS = {
  ROLE_SETUP: 1,
  PERMISSION_SETUP: 2,
  REVIEW_CONFIRM: 3,
  TOTAL_STEPS: 3,
} as const;

export type WizardStep = typeof WIZARD_STEPS[keyof Omit<typeof WIZARD_STEPS, 'TOTAL_STEPS'>];
