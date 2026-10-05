/**
 * Date Formatting Utilities
 * Provides standardized date/time formatting functions matching backend back-end format (d/m/Y)
 */

import { format, parse, parseISO, isValid, formatDistanceToNow } from 'date-fns';
import { DATE_FORMATS } from '@/shared/config/constant';

/**
 * Format a date to display format (dd/MM/yyyy)
 * @param date - Date object, ISO string, or any valid date input
 * @returns Formatted date string in dd/MM/yyyy format
 */
export function formatDate(date: Date | string | null | undefined): string {
  if (!date) return '';
  
  try {
    const dateObj = typeof date === 'string' ? parseISO(date) : date;
    if (!isValid(dateObj)) return '';
    return format(dateObj, DATE_FORMATS.DATE);
  } catch {
    return '';
  }
}

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
 * Format a datetime to display format (dd/MM/yyyy HH:mm:ss)
 * @param date - Date object, ISO string, or any valid date input
 * @returns Formatted datetime string
 */
export function formatDateTime(date: Date | string | null | undefined): string {
  if (!date) return '';
  
  try {
    const dateObj = typeof date === 'string' ? parseISO(date) : date;
    if (!isValid(dateObj)) return '';
    return format(dateObj, DATE_FORMATS.FULL);
  } catch {
    return '';
  }
}

/**
 * Format a datetime to backend format (dd/MM/yyyy HH:mm:ss)
 * Use this when sending date times to the back-end API
 * @param date - Date object, ISO string, or any valid date input
 * @returns Formatted datetime string for backend
 */
export function formatDateTimeForBackend(date: Date | string | null | undefined): string {
  if (!date) return '';
  
  try {
    const dateObj = typeof date === 'string' ? parseISO(date) : date;
    if (!isValid(dateObj)) return '';
    return format(dateObj, DATE_FORMATS.FULL); // dd/MM/yyyy HH:mm:ss
  } catch {
    return '';
  }
}

/**
 * Parse a date string from backend format (dd/MM/yyyy) to Date object
 * @param dateString - Date string in dd/MM/yyyy format
 * @returns Date object or null if invalid
 */
export function parseDateFromBackend(dateString: string | null | undefined): Date | null {
  if (!dateString) return null;
  
  try {
    const parsed = parse(dateString, DATE_FORMATS.DATE, new Date());
    return isValid(parsed) ? parsed : null;
  } catch {
    return null;
  }
}

/**
 * Parse a datetime string from backend format to Date object
 * @param dateTimeString - DateTime string in dd/MM/yyyy HH:mm:ss format
 * @returns Date object or null if invalid
 */
export function parseDateTimeFromBackend(dateTimeString: string | null | undefined): Date | null {
  if (!dateTimeString) return null;
  
  try {
    const parsed = parse(dateTimeString, DATE_FORMATS.FULL, new Date());
    return isValid(parsed) ? parsed : null;
  } catch {
    return null;
  }
}

/**
 * Format time only (HH:mm:ss)
 * @param date - Date object, ISO string, or any valid date input
 * @returns Formatted time string
 */
export function formatTime(date: Date | string | null | undefined): string {
  if (!date) return '';
  
  try {
    const dateObj = typeof date === 'string' ? parseISO(date) : date;
    if (!isValid(dateObj)) return '';
    return format(dateObj, DATE_FORMATS.TIME);
  } catch {
    return '';
  }
}

/**
 * Check if a date string is valid
 * @param dateString - Date string to validate
 * @returns true if valid, false otherwise
 */
export function isValidDate(dateString: string | null | undefined): boolean {
  if (!dateString) return false;
  
  try {
    const parsed = parseISO(dateString);
    return isValid(parsed);
  } catch {
    return false;
  }
}

/**
 * Get current date formatted for display
 * @returns Current date in dd/MM/yyyy format
 */
export function getCurrentDate(): string {
  return format(new Date(), DATE_FORMATS.DATE);
}

/**
 * Get current datetime formatted for display
 * @returns Current datetime in dd/MM/yyyy HH:mm:ss format
 */
export function getCurrentDateTime(): string {
  return format(new Date(), DATE_FORMATS.FULL);
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

/**
 * Get a date from now by milliseconds
 * @param ms - Milliseconds to subtract from now
 * @returns Date object
 */
export function getDateFromNow(ms: number): Date {
  return new Date(Date.now() - ms);
}
