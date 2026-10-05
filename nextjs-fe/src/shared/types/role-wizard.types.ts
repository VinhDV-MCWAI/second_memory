/**
 * ============================================================================
 * ROLE WIZARD TYPES & INTERFACES
 * ============================================================================
 * Type definitions for Role Wizard feature components
 */

import type { RoleMst, ApiMst, FeatureMst } from './api';
import type { RoleFormData } from '@/shared/validation/validation';

// ============================================================================
// WIZARD STATE TYPES
// ============================================================================

export interface WizardState {
  step: number;
  roleData: Partial<RoleFormData>;
  selectedApiIds: number[];
}

export interface RoleWizardDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  initialData?: RoleMst | null;
  onSuccess: () => void;
}

// ============================================================================
// STEP COMPONENT PROPS
// ============================================================================

export interface Step1RoleSetupProps {
  data: Partial<RoleFormData>;
  onChange: (data: Partial<RoleFormData>) => void;
}

export interface Step2PermissionSetupProps {
  selectedApiIds: number[];
  onSelectedApisChange: (apiIds: number[]) => void;
}

export interface Step3ReviewConfirmProps {
  roleData: Partial<RoleFormData>;
  selectedApiIds: number[];
  isEdit: boolean;
}

// ============================================================================
// DIALOG CONFIRM PROPS
// ============================================================================

export interface ConfirmCancelDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onConfirm: () => void;
}

export interface ConfirmSubmitDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onConfirm: () => void;
  isLoading?: boolean;
}

// ============================================================================
// PERMISSION SETUP HELPER TYPES
// ============================================================================

export interface GroupedApisByFeature {
  [featureId: number]: {
    feature: FeatureMst;
    apis: ApiMst[];
  };
}

export interface ApisByFeatureGroup {
  feature: FeatureMst;
  apis: ApiMst[];
}
