/**
 * Type Guards for Runtime Type Checking
 *
 * These functions help ensure type safety at runtime,
 * especially when dealing with API responses or user input.
 */

/**
 * Check if value is a valid string
 */
export function isString(value: unknown): value is string {
  return typeof value === 'string';
}

/**
 * Check if value is a valid array
 */
export function isArray<T>(value: unknown): value is T[] {
  return Array.isArray(value);
}

/**
 * Check if value is a valid object (not null, not array)
 */
export function isObject(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}
