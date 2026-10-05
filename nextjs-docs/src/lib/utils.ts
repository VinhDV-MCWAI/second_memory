import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

/**
 * Generate slug from title for anchor links
 */
export function slugify(text: string): string {
  return text
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '')
    .replace(/[\s_-]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

/**
 * Debounce function for search input
 */
export function debounce<A extends unknown[]>(
  func: (...args: A) => unknown,
  wait: number
): (...args: A) => void {
  let timeout: NodeJS.Timeout;
  return (...args: A) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
}
