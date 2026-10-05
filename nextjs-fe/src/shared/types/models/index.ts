/**
 * Common Types and Enums
 */

// Status enum
export enum Status {
  INACTIVE = 0,
  ACTIVE = 1,
  PENDING = 2,
  ARCHIVED = 3,
}

export const StatusLabels: Record<Status, string> = {
  [Status.INACTIVE]: 'Inactive',
  [Status.ACTIVE]: 'Active',
  [Status.PENDING]: 'Pending',
  [Status.ARCHIVED]: 'Archived',
};

// Gender enum
export enum Gender {
  FEMALE = 0,
  MALE = 1,
  OTHER = 2,
}

export const GenderLabels: Record<Gender, string> = {
  [Gender.FEMALE]: 'Female',
  [Gender.MALE]: 'Male',
  [Gender.OTHER]: 'Other',
};

// HTTP Methods
export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';

// Link Target
export type LinkTarget = '_self' | '_blank' | '_parent' | '_top';

// Re-export model types
export * from './master';
export * from './management';
export * from './history';
