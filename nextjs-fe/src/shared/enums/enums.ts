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
// AdminRole (app/Enums/AdminRole.php, ADR-0005)
// An owner may change data, a viewer may only read it
// ==========================================
export enum AdminRole {
  OWNER = 'owner',
  VIEWER = 'viewer',
}

export const AdminRoleLabels: Record<AdminRole, string> = {
  [AdminRole.OWNER]: 'Owner',
  [AdminRole.VIEWER]: 'Viewer',
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
// AuditEvent (app/Enums/AuditEvent.php, ADR-0006)
// What an audit_log row records
// ==========================================
export enum AuditEvent {
  CREATED = 'created',
  UPDATED = 'updated',
  DELETED = 'deleted',
  LOGGED_IN = 'logged_in',
  LOGGED_OUT = 'logged_out',
  LOGIN_FAILED = 'login_failed',
}

// ==========================================
// DEPRECATED - Remove after migration
// Legacy Status enum (use IsActive or specific status enums instead)
// ==========================================
/** @deprecated Use IsActive or specific status enums like AdminStatus. */
export const Status = IsActive;
/** @deprecated Use IsActiveLabels or specific status labels */
export const StatusLabels = IsActiveLabels;

// ==========================================
// SkillLevel (app/Enums/SkillLevel.php, REQ-002)
// ==========================================
export enum SkillLevel {
  LEARNING = 1,
  WITH_HELP = 2,
  INDEPENDENT = 3,
  CAN_TEACH = 4,
}

export const SkillLevelLabels: Record<SkillLevel, string> = {
  [SkillLevel.LEARNING]: 'Learning',
  [SkillLevel.WITH_HELP]: 'Can use with help',
  [SkillLevel.INDEPENDENT]: 'Independent',
  [SkillLevel.CAN_TEACH]: 'Can teach others',
};

// ==========================================
// EvidenceType, EvidenceSource (app/Enums, REQ-002 US-2 / US-4)
// ==========================================
export enum EvidenceType {
  PR = 'pr',
  ADR = 'adr',
  INCIDENT = 'incident',
  NOTE = 'note',
  OTHER = 'other',
}

export const EvidenceTypeLabels: Record<EvidenceType, string> = {
  [EvidenceType.PR]: 'Pull request',
  [EvidenceType.ADR]: 'Decision record',
  [EvidenceType.INCIDENT]: 'Incident',
  [EvidenceType.NOTE]: 'Note',
  [EvidenceType.OTHER]: 'Other',
};

export enum EvidenceSource {
  MANUAL = 'manual',
  OBSIDIAN = 'obsidian',
}

export const EvidenceSourceLabels: Record<EvidenceSource, string> = {
  [EvidenceSource.MANUAL]: 'Manual',
  [EvidenceSource.OBSIDIAN]: 'Obsidian',
};

// ==========================================
// GoalStatus (app/Enums/GoalStatus.php, REQ-002 US-6)
// `achieved` is set by a level change only
// ==========================================
export enum GoalStatus {
  OPEN = 'open',
  ACHIEVED = 'achieved',
  DROPPED = 'dropped',
}

export const GoalStatusLabels: Record<GoalStatus, string> = {
  [GoalStatus.OPEN]: 'Open',
  [GoalStatus.ACHIEVED]: 'Achieved',
  [GoalStatus.DROPPED]: 'Dropped',
};
