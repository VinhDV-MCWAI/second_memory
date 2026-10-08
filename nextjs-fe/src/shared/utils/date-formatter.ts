/**
 * Date Formatting Utilities
 * Provides standardized date/time formatting functions matching backend back-end format (d/m/Y)
 */

import { format, parseISO, isValid, formatDistanceToNow } from 'date-fns';
import { DATE_FORMATS } from '@/shared/config';

/**
 * Format a date to HTML input format (yyyy-MM-dd)
 * @param date - Date object, ISO string, or any valid date input
 * @returns Formatted date string in yyyy-MM-dd format for HTML inputs
 */
export function formatDateForInput(date: Date | string | null | undefined): string {
  if (!date) return '';

  try {
    const dateObj = typeof date === 'string' ? parseISO(date) : date;
    if (!isValid(dateObj)) return '';
    return format(dateObj, DATE_FORMATS.DATE_INPUT);
  } catch {
    return '';
  }
}

/**
 * Format a date to backend format (dd/MM/yyyy)
 * Use this when sending dates to the back-end API
 * @param date - Date object, ISO string, or any valid date input
 * @returns Formatted date string in dd/MM/yyyy format for backend
 */
export function formatDateForBackend(date: Date | string | null | undefined): string {
  if (!date) return '';

  try {
    const dateObj = typeof date === 'string' ? parseISO(date) : date;
    if (!isValid(dateObj)) return '';
    return format(dateObj, DATE_FORMATS.DATE); // dd/MM/yyyy matches back-end d/m/Y
  } catch {
    return '';
  }
}

/**
 * Format timestamp to relative time (e.g., "2 hours ago")
 * @param date - Date object or timestamp
 * @returns Formatted relative time string or fallback to locale time string
 */
export function formatTimestamp(date: Date | string | null | undefined): string {
  if (!date) return '';

  try {
    const dateObj = typeof date === 'string' ? parseISO(date) : date;
    if (!isValid(dateObj)) return '';

    return formatDistanceToNow(dateObj, { addSuffix: true });
  } catch {
    try {
      const dateObj = typeof date === 'string' ? new Date(date) : date;
      return dateObj.toLocaleTimeString();
    } catch {
      return '';
    }
  }
}
