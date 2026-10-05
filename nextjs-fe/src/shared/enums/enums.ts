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
// UserStatus (app/Enums/UserStatus.php)
// Status for User users
// ==========================================
export enum UserStatus {
  INACTIVE = 0,
  ACTIVE = 1,
  WAITING = 2,
  SUSPENDED = 3,
}

export const UserStatusLabels: Record<UserStatus, string> = {
  [UserStatus.INACTIVE]: 'Inactive',
  [UserStatus.ACTIVE]: 'Active',
  [UserStatus.WAITING]: 'Waiting',
  [UserStatus.SUSPENDED]: 'Suspended',
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

export const IsDeleteLabels: Record<IsDelete, string> = {
  [IsDelete.FALSE]: 'Not Deleted',
  [IsDelete.TRUE]: 'Deleted',
};

// ==========================================
// UploadStatus (app/Enums/UploadStatus.php)
// File upload processing status
// ==========================================
export enum UploadStatus {
  PROCESSING = 1,
  COMPLETED = 2,
  FAILED = 3,
}

export const UploadStatusLabels: Record<UploadStatus, string> = {
  [UploadStatus.PROCESSING]: 'Processing',
  [UploadStatus.COMPLETED]: 'Completed',
  [UploadStatus.FAILED]: 'Failed',
};

// ==========================================
// CategoryStatus (app/Enums/CategoryStatus.php)
// ==========================================
export enum CategoryStatus {
  INACTIVE = 0,
  ACTIVE = 1,
  ARCHIVED = 2,
}

export const CategoryStatusLabels: Record<CategoryStatus, string> = {
  [CategoryStatus.INACTIVE]: 'Inactive',
  [CategoryStatus.ACTIVE]: 'Active',
  [CategoryStatus.ARCHIVED]: 'Archived',
};

// ==========================================
// DepartmentStatus (app/Enums/DepartmentStatus.php)
// ==========================================
export enum DepartmentStatus {
  INACTIVE = 0,
  ACTIVE = 1,
  DRAFT = 2,
  ARCHIVED = 3,
}

export const DepartmentStatusLabels: Record<DepartmentStatus, string> = {
  [DepartmentStatus.INACTIVE]: 'Inactive',
  [DepartmentStatus.ACTIVE]: 'Active',
  [DepartmentStatus.DRAFT]: 'Draft',
  [DepartmentStatus.ARCHIVED]: 'Archived',
};

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
// EntryStatus (app/Enums/EntryStatus.php)
// ==========================================
export enum EntryStatus {
  INACTIVE = 0,
  ACTIVE = 1,
  WAITING = 2,
  SUSPENDED = 3,
}

export const EntryStatusLabels: Record<EntryStatus, string> = {
  [EntryStatus.INACTIVE]: 'Inactive',
  [EntryStatus.ACTIVE]: 'Active',
  [EntryStatus.WAITING]: 'Waiting',
  [EntryStatus.SUSPENDED]: 'Suspended',
};

// ==========================================
// SocialStatus (app/Enums/SocialStatus.php)
// ==========================================
export enum SocialStatus {
  INACTIVE = 0,
  ACTIVE = 1,
  PENDING = 2,
}

export const SocialStatusLabels: Record<SocialStatus, string> = {
  [SocialStatus.INACTIVE]: 'Inactive',
  [SocialStatus.ACTIVE]: 'Active',
  [SocialStatus.PENDING]: 'Pending',
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

export const ActionTypeLabels: Record<ActionType, string> = {
  [ActionType.CREATE]: 'Create',
  [ActionType.UPDATE]: 'Update',
  [ActionType.DELETE]: 'Delete',
};

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
/** @deprecated Use IsActive or specific status enums like AdminStatus, CategoryStatus, etc. */
export const Status = IsActive;
/** @deprecated Use IsActiveLabels or specific status labels */
export const StatusLabels = IsActiveLabels;

// ==========================================
// IsDisplay (app/Enums/IsDisplay.php)
// Boolean-like status for display visibility
// ==========================================
export enum IsDisplay {
  FALSE = 0,
  TRUE = 1,
}

export const IsDisplayLabels: Record<IsDisplay, string> = {
  [IsDisplay.FALSE]: 'Hidden',
  [IsDisplay.TRUE]: 'Visible',
};
