/**
 * Enums matching back-end (app/Enums/)
 * Auto-synced from back-end api
 */

// ==========================================
// StatusEnum (app/Enums/StatusEnum.php)
// Generic status for content/posts
// ==========================================
export enum StatusEnum {
  DRAFT = 0,
  PUBLISHED = 1,
  ARCHIVED = 2,
}

export const StatusEnumLabels: Record<StatusEnum, string> = {
  [StatusEnum.DRAFT]: 'Draft',
  [StatusEnum.PUBLISHED]: 'Published',
  [StatusEnum.ARCHIVED]: 'Archived',
};

// ==========================================
// AdminStatus (app/Enums/AdminStatus.php)
// Status for Admin users
// ==========================================
export enum AdminStatus {
  INACTIVE = 0,
  ACTIVE = 1,
  WAITING = 2,
  SUSPENDED = 3,
}

export const AdminStatusLabels: Record<AdminStatus, string> = {
  [AdminStatus.INACTIVE]: 'Inactive',
  [AdminStatus.ACTIVE]: 'Active',
  [AdminStatus.WAITING]: 'Waiting',
  [AdminStatus.SUSPENDED]: 'Suspended',
};

// ==========================================
// Gender (app/Enums/Gender.php)
// ==========================================
export enum Gender {
  MALE = 1,
  FEMALE = 2,
  OTHER = 3,
}

export const GenderLabels: Record<Gender, string> = {
  [Gender.MALE]: 'Male',
  [Gender.FEMALE]: 'Female',
  [Gender.OTHER]: 'Other',
};

// ==========================================
// IsActive (app/Enums/IsActive.php)
// Boolean-like status
// ==========================================
export enum IsActive {
  FALSE = 0,
  TRUE = 1,
}

export const IsActiveLabels: Record<IsActive, string> = {
  [IsActive.FALSE]: 'Inactive',
  [IsActive.TRUE]: 'Active',
};

// ==========================================
// IsDelete (app/Enums/IsDelete.php)
// Soft delete flag
// ==========================================
export enum IsDelete {
  FALSE = 0,
  TRUE = 1,
}

// ==========================================
// UploadStatus (app/Enums/UploadStatus.php)
// File upload processing status
// ==========================================
export enum UploadStatus {
  PROCESSING = 1,
  COMPLETED = 2,
  FAILED = 3,
}

// ==========================================
// FeatureStatus (app/Enums/FeatureStatus.php)
// ==========================================
export enum FeatureStatus {
  INACTIVE = 0,
  ACTIVE = 1,
  DRAFT = 2,
  ARCHIVED = 3,
}

export const FeatureStatusLabels: Record<FeatureStatus, string> = {
  [FeatureStatus.INACTIVE]: 'Inactive',
  [FeatureStatus.ACTIVE]: 'Active',
  [FeatureStatus.DRAFT]: 'Draft',
  [FeatureStatus.ARCHIVED]: 'Archived',
};

// ==========================================
// ActionType (app/Enums/ActionType.php)
// History/Audit action types
// ==========================================
export enum ActionType {
  CREATE = 1,
  UPDATE = 2,
  DELETE = 3,
}

// ==========================================
// TypeOfMethod (app/Enums/TypeOfMethod.php)
// HTTP methods for API permissions
// ==========================================
export enum TypeOfMethod {
  GET = 0,
  POST = 1,
  PUT = 2,
  PATCH = 3,
  DELETE = 4,
}

export const TypeOfMethodLabels: Record<TypeOfMethod, string> = {
  [TypeOfMethod.GET]: 'GET',
  [TypeOfMethod.POST]: 'POST',
  [TypeOfMethod.PUT]: 'PUT',
  [TypeOfMethod.PATCH]: 'PATCH',
  [TypeOfMethod.DELETE]: 'DELETE',
};

// ==========================================
// DEPRECATED - Remove after migration
// Legacy Status enum (use IsActive or specific status enums instead)
// ==========================================
/** @deprecated Use IsActive or specific status enums like AdminStatus, FeatureStatus, etc. */
export const Status = IsActive;
/** @deprecated Use IsActiveLabels or specific status labels */
export const StatusLabels = IsActiveLabels;
