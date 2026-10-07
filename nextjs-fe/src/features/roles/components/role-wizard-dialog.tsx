'use client';

import { useState, useCallback, useEffect, useLayoutEffect, useRef } from 'react';
import { useTranslations } from 'next-intl';
import { useActionLock } from '@/shared/hooks/use-action-lock';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { UI_CONSTANTS } from '@/shared/config';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import type { RoleFormData } from '@/shared/validation/validation';
import {
  Step1RoleSetup,
  Step2PermissionSetup,
  Step3ReviewConfirm,
  ConfirmCancelDialog,
  ConfirmSubmitDialog,
} from './role-wizard-steps';
import { WIZARD_STEPS } from '@/features/roles/role-wizard.constant';
import type { WizardState, RoleWizardDialogProps } from '@/features/roles/role-wizard.types';
import { notification } from '@/shared/utils';
import {
  buildPermissionUpdate,
  fetchAssignedApiIds,
  savePermissions,
  saveRole,
} from '@/features/roles/role-wizard.api';
import { WizardStepIndicator } from './wizard-step-indicator';
import { WizardFooter } from './wizard-footer';

const EMPTY_WIZARD_STATE: WizardState = {
  step: WIZARD_STEPS.ROLE_SETUP,
  roleData: {
    name: '',
    permission: '',
    is_active: true,
  },
  selectedApiIds: [],
};

export function RoleWizardDialog({
  open,
  onOpenChange,
  initialData,
  onSuccess,
}: RoleWizardDialogProps) {
  const tCommon = useTranslations('common');
  const tWizard = useTranslations('roleWizard');

  const isEdit = !!initialData;
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { execute, isLoading: isActionProcessing } = useActionLock({
    delay: UI_CONSTANTS.ACTION_DELAY_MS,
  });

  const [wizardState, setWizardState] = useState<WizardState>(EMPTY_WIZARD_STATE);
  const [showCancelConfirm, setShowCancelConfirm] = useState(false);
  const [showSubmitConfirm, setShowSubmitConfirm] = useState(false);
  const initRef = useRef(false);
  const [initialAssignedIds, setInitialAssignedIds] = useState<number[]>([]);

  // Initialize wizard state when initialData or open changes
  useLayoutEffect(() => {
    if (!open || initRef.current) return;

    const initState: WizardState = initialData
      ? {
          step: WIZARD_STEPS.ROLE_SETUP,
          roleData: {
            name: initialData.name,
            permission: initialData.permission,
            is_active: initialData.is_active,
          },
          // Filled by the fetch below
          selectedApiIds: [],
        }
      : EMPTY_WIZARD_STATE;

    // Use microtask to defer state update
    queueMicrotask(() => {
      initRef.current = true;
      setInitialAssignedIds([]);
      setWizardState(initState);

      // If Edit mode, fetch the actual assigned APIs
      if (initialData) {
        fetchAssignedApiIds(initialData.id)
          .then((assignedIds) => {
            setInitialAssignedIds(assignedIds);
            setWizardState((prev) => ({ ...prev, selectedApiIds: assignedIds }));
          })
          .catch((err) => {
            console.error('Failed to fetch assigned APIs', err);
            // The role list does not include its APIs, so start from an empty selection
            setInitialAssignedIds([]);
            setWizardState((prev) => ({ ...prev, selectedApiIds: [] }));
          });
      }
    });
  }, [open, initialData]);

  // Reset init ref when dialog closes
  useEffect(() => {
    if (!open) {
      initRef.current = false; // state is re-initialized on the next open
    }
  }, [open]);

  const handleStepChange = useCallback((newStep: number) => {
    setWizardState((prev) => ({ ...prev, step: newStep }));
  }, []);

  const handleRoleDataChange = useCallback((data: Partial<RoleFormData>) => {
    setWizardState((prev) => ({ ...prev, roleData: { ...prev.roleData, ...data } }));
  }, []);

  const handleSelectedApisChange = useCallback((apiIds: number[]) => {
    setWizardState((prev) => ({ ...prev, selectedApiIds: apiIds }));
  }, []);

  const handleCancel = () => {
    setShowCancelConfirm(true);
  };

  const handleConfirmCancel = () => {
    setShowCancelConfirm(false);
    onOpenChange(false);
    setWizardState(EMPTY_WIZARD_STATE);
  };

  const handleNext = () => {
    if (wizardState.step < WIZARD_STEPS.TOTAL_STEPS) {
      handleStepChange(wizardState.step + 1);
    }
  };

  const handlePrev = () => {
    if (wizardState.step > WIZARD_STEPS.ROLE_SETUP) {
      handleStepChange(wizardState.step - 1);
    }
  };

  const handleSkipToReview = () => {
    // Skip from step 2 to step 3
    handleStepChange(WIZARD_STEPS.REVIEW_CONFIRM);
  };

  const handleSubmit = async () => {
    setShowSubmitConfirm(true);
  };

  const handleConfirmSubmit = async () => {
    setShowSubmitConfirm(false);

    // Manual loading state
    setIsSubmitting(true);

    await execute(async () => {
      try {
        // 1. Role (create or update)
        let roleId: number;
        try {
          roleId = await saveRole(wizardState.roleData, isEdit ? initialData : null);
        } catch (error) {
          handleBindErrors(error, () => {});
          setIsSubmitting(false);
          return;
        }

        // 2. Permissions (junction update); on failure keep the dialog open and the list as is
        const permissionUpdate = buildPermissionUpdate(
          roleId,
          isEdit ? initialAssignedIds : [],
          wizardState.selectedApiIds,
        );
        if (permissionUpdate) {
          try {
            await savePermissions(permissionUpdate);
          } catch (error) {
            console.error('Permission update failed:', error);
            notification.error(
              tCommon('error') +
                ': ' +
                (isEdit
                  ? 'Failed to update permissions'
                  : 'Role created but failed to set permissions'),
            );
            setIsSubmitting(false);
            return;
          }
        }

        // Success Path (Role Success + (No Perm Changes OR Perm Success))
        notification.success(
          isEdit ? tCommon('updatedSuccessfully') : tCommon('createdSuccessfully'),
        );
        onSuccess(); // Close and Refresh List
        onOpenChange(false);

        setWizardState(EMPTY_WIZARD_STATE);
        setInitialAssignedIds([]);
      } catch (error: unknown) {
        console.error(error);
        handleBindErrors(error, () => {});
      } finally {
        setIsSubmitting(false);
      }
    });
  };

  const renderStepContent = () => {
    switch (wizardState.step) {
      case WIZARD_STEPS.ROLE_SETUP:
        return <Step1RoleSetup data={wizardState.roleData} onChange={handleRoleDataChange} />;
      case WIZARD_STEPS.PERMISSION_SETUP:
        return (
          <Step2PermissionSetup
            selectedApiIds={wizardState.selectedApiIds}
            onSelectedApisChange={handleSelectedApisChange}
          />
        );
      case WIZARD_STEPS.REVIEW_CONFIRM:
        return (
          <Step3ReviewConfirm
            roleData={wizardState.roleData}
            selectedApiIds={wizardState.selectedApiIds}
            isEdit={isEdit}
          />
        );
      default:
        return null;
    }
  };

  const getStepTitle = () => {
    const titles: Record<number, string> = {
      [WIZARD_STEPS.ROLE_SETUP]: `${tWizard('step')} 1: ${tWizard('roleSetup')}`,
      [WIZARD_STEPS.PERMISSION_SETUP]: `${tWizard('step')} 2: ${tWizard('permissionSetup')}`,
      [WIZARD_STEPS.REVIEW_CONFIRM]: `${tWizard('step')} 3: ${tCommon('review')} & ${tCommon('confirm')}`,
    };
    return titles[wizardState.step] || '';
  };

  const isStep1Valid = wizardState.roleData.name && wizardState.roleData.permission;

  return (
    <>
      <Dialog
        open={open}
        onOpenChange={(newOpen) => {
          if (!newOpen) {
            handleCancel();
          }
        }}
      >
        <DialogContent className="!top-[50%] !left-[50%] flex !h-[80vh] !max-h-[80vh] !w-[80vw] !max-w-[80vw] !-translate-x-1/2 !-translate-y-1/2 flex-col !gap-0 !overflow-hidden !rounded-lg !border !p-6">
          <DialogTitle className="sr-only">{getStepTitle()}</DialogTitle>
          <DialogDescription className="sr-only">Role Creation Wizard</DialogDescription>
          <div className="mb-6 flex shrink-0 flex-col items-center justify-center gap-4">
            <WizardStepIndicator currentStep={wizardState.step} />
          </div>

          <div className="flex min-h-0 flex-1 flex-col overflow-hidden">
            <div className="min-h-0 flex-1 overflow-y-auto scroll-smooth">
              {renderStepContent()}
            </div>
          </div>

          <WizardFooter
            step={wizardState.step}
            isEdit={isEdit}
            busy={isSubmitting || isActionProcessing}
            canProceed={!!isStep1Valid}
            onCancel={handleCancel}
            onPrev={handlePrev}
            onSkip={handleSkipToReview}
            onNext={handleNext}
            onSubmit={handleSubmit}
          />
        </DialogContent>
      </Dialog>

      {/* Confirm Cancel Dialog */}
      <ConfirmCancelDialog
        open={showCancelConfirm}
        onOpenChange={setShowCancelConfirm}
        onConfirm={handleConfirmCancel}
      />

      {/* Confirm Submit Dialog */}
      <ConfirmSubmitDialog
        open={showSubmitConfirm}
        onOpenChange={setShowSubmitConfirm}
        onConfirm={handleConfirmSubmit}
        isLoading={isSubmitting || isActionProcessing}
      />
    </>
  );
}
