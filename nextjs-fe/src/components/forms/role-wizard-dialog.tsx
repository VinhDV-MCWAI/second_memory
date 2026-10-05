'use client';

import { useState, useCallback, useEffect, useLayoutEffect, useRef } from 'react';
import { useTranslations } from 'next-intl';
import { useActionLock } from '@/shared/hooks/useActionLock';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { UI_CONSTANTS } from '@/shared/config';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { ENDPOINTS, API_ENDPOINTS, apiClient } from '@/shared/api';
import type { RoleFormData } from '@/shared/validation/validation';
import { Step1RoleSetup, Step2PermissionSetup, Step3ReviewConfirm, ConfirmCancelDialog, ConfirmSubmitDialog } from './role-wizard-steps';
import { WIZARD_STEPS } from '@/shared/config/role-wizard.constant';
import type { WizardState, RoleWizardDialogProps } from '@/shared/types/role-wizard.types';
import { notification } from '@/shared/utils';

export function RoleWizardDialog({
  open,
  onOpenChange,
  initialData,
  onSuccess,
}: RoleWizardDialogProps) {
  const tCommon = useTranslations('common');
  /* eslint-disable @typescript-eslint/no-unused-vars */
  const tCrud = useTranslations('crud');
  /* eslint-enable @typescript-eslint/no-unused-vars */
  const tWizard = useTranslations('roleWizard');

  const isEdit = !!initialData;
  // const { create, update, loading } = useCrud<RoleMst>(ENDPOINTS.MASTER.ROLE); // Removed useCrud
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { execute, isLoading: isActionProcessing } = useActionLock({ 
    delay: UI_CONSTANTS.ACTION_DELAY_MS 
  });

  const [wizardState, setWizardState] = useState<WizardState>({
    step: 1,
    roleData: {
      name: '',
      permission: '',
      is_active: true,
    },
    selectedApiIds: [],
  });

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
          // We start with empty, but will fetch the real ones below
          selectedApiIds: [], 
        }
      : {
          step: WIZARD_STEPS.ROLE_SETUP,
          roleData: {
            name: '',
            permission: '',
            is_active: true,
          },
          selectedApiIds: [],
        };

    // Use microtask to defer state update
    queueMicrotask(() => {
      initRef.current = true;
      setInitialAssignedIds([]);
      setWizardState(initState);
      
      // If Edit mode, fetch the actual assigned APIs
      if (initialData) {
        // We use an async IIFE here
        (async () => {
          try {
             // We need to fetch the assigned APIs dynamically because initialData from list might be incomplete
             const response = await apiClient.get< { data: Array<{ api_mst_id: number }> } | Array<{ api_mst_id: number }> >(API_ENDPOINTS.JUNCTION.API_ROLE + '/list', {
                params: { 
                  role_mst_id: initialData.id,
                  per_page: 9999 // Fetch all (using large number as -1 might default to 15)
                }
             });
             
             // Handle response structure which might be paginated or array
             const rawData = response.data;
             const assignedList = Array.isArray(rawData) ? rawData : (rawData as { data: Array<{ api_mst_id: number }> }).data || [];
             
             // Extract IDs. The list api usually returns objects like { role_mst_id, api_mst_id }
             const assignedIds = assignedList.map((item: { api_mst_id: number }) => item.api_mst_id);
             
             setInitialAssignedIds(assignedIds);
             setWizardState(prev => ({
               ...prev,
               selectedApiIds: assignedIds
             }));
          } catch (err) {
            console.error('Failed to fetch assigned APIs', err);
            // Fallback to initialData if fetch fails, though likely empty
            const fallbackIds = initialData.apis?.map(api => api.id) || [];
            setInitialAssignedIds(fallbackIds);
            setWizardState(prev => ({ ...prev, selectedApiIds: fallbackIds }));
          }
        })();
      }
    });
  }, [open, initialData]);

  // Reset init ref when dialog closes
  useEffect(() => {
    if (!open) {
      initRef.current = false;
      // We don't need to reset state here as it will be reset on next open
    }
  }, [open]);

  const handleStepChange = useCallback((newStep: number) => {
    setWizardState(prev => ({ ...prev, step: newStep }));
  }, []);

  const handleRoleDataChange = useCallback((data: Partial<RoleFormData>) => {
    setWizardState(prev => ({
      ...prev,
      roleData: { ...prev.roleData, ...data },
    }));
  }, []);

  const handleSelectedApisChange = useCallback((apiIds: number[]) => {
    setWizardState(prev => ({
      ...prev,
      selectedApiIds: apiIds,
    }));
  }, []);

  const handleCancel = () => {
    setShowCancelConfirm(true);
  };

  const handleConfirmCancel = () => {
    setShowCancelConfirm(false);
    onOpenChange(false);
    // Reset wizard state
    setWizardState({
      step: WIZARD_STEPS.ROLE_SETUP,
      roleData: {
        name: '',
        permission: '',
        is_active: true,
      },
      selectedApiIds: [],
    });
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
        // 1. Submit Role Data (Create or Update)
        const rolePayload = {
          name: wizardState.roleData.name,
          permission: wizardState.roleData.permission,
          is_active: wizardState.roleData.is_active,
          is_delete: false,
        };

        let roleId: number;
        let isRoleSuccess = false;

        try {
          if (isEdit && initialData) {
            // Update Role
            await apiClient.put<{ data: number }>(`${ENDPOINTS.MASTER.ROLE}/update/${initialData.id}`, {
              id: initialData.id,
              ...rolePayload
            });
            roleId = initialData.id; // Or res.data if strictly needed
            isRoleSuccess = true;
          } else {
            // Create Role
            const res = await apiClient.post<number>(`${ENDPOINTS.MASTER.ROLE}/store`, rolePayload);
            roleId = res.data;
            isRoleSuccess = true;
          }
        } catch (error) {
           handleBindErrors(error, () => {});
           setIsSubmitting(false); // Stop loading on role error
           return; 
        }

        if (!isRoleSuccess) {
           setIsSubmitting(false);
           return;
        }

        // 2. Submit Permissions (Junction Update)
        const currentApiIds = wizardState.selectedApiIds;
        const initialApiIds = isEdit ? initialAssignedIds : [];

        const toInsertIds = currentApiIds.filter(id => !initialApiIds.includes(id));
        const toDeleteIds = initialApiIds.filter(id => !currentApiIds.includes(id));

        const hasChanges = toInsertIds.length > 0 || toDeleteIds.length > 0;

        if (hasChanges) {
          const updateData: Record<string, unknown> = {
            role_mst_id: roleId,
          };

          if (toInsertIds.length > 0) {
            updateData.insert = toInsertIds.map(apiId => ({
              role_mst_id: roleId,
              api_mst_id: apiId,
            }));
          }

          if (toDeleteIds.length > 0) {
            updateData.delete = toDeleteIds.map(apiId => ({
              role_mst_id: roleId,
              api_mst_id: apiId,
            }));
          }

          try {
            await apiClient.put(API_ENDPOINTS.JUNCTION.API_ROLE + '/update', updateData);
          } catch (error) {
            console.error('Permission update failed:', error);
            // REQUIREMENT: If Permission fails, Show Error/Warning and DO NOT UPDATE LIST
            notification.error(tCommon('error') + ': ' + (isEdit ? 'Failed to update permissions' : 'Role created but failed to set permissions')); 
            
            // We do NOT call onSuccess here.
            // We stop loading.
            setIsSubmitting(false);
            return;
          }
        }
        
        // Success Path (Role Success + (No Perm Changes OR Perm Success))
        notification.success(isEdit ? tCommon('updatedSuccessfully') : tCommon('createdSuccessfully'));
        onSuccess(); // Close and Refresh List
        onOpenChange(false);
        
        // Reset state
        setWizardState({
          step: WIZARD_STEPS.ROLE_SETUP,
          roleData: {
            name: '',
            permission: '',
            is_active: true,
          },
          selectedApiIds: [],
        });
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
        return (
          <Step1RoleSetup
            data={wizardState.roleData}
            onChange={handleRoleDataChange}
          />
        );
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
  const isLastStep = wizardState.step === WIZARD_STEPS.REVIEW_CONFIRM;
  const isFirstStep = wizardState.step === WIZARD_STEPS.ROLE_SETUP;

  return (
    <>
      <Dialog open={open} onOpenChange={(newOpen) => {
        if (!newOpen) {
          handleCancel();
        }
      }}>
        <DialogContent className="!w-[80vw] !h-[80vh] !max-w-[80vw] !max-h-[80vh] !overflow-hidden !p-6 !gap-0 !left-[50%] !top-[50%] !-translate-x-1/2 !-translate-y-1/2 !rounded-lg !border flex flex-col">
          <DialogTitle className="sr-only">{getStepTitle()}</DialogTitle>
          <DialogDescription className="sr-only">Role Creation Wizard</DialogDescription>
          <div className="flex flex-col items-center justify-center mb-6 shrink-0 gap-4">
            {/* Step indicators - centered */}
            <div className="flex shrink-0 mb-8">
              {[WIZARD_STEPS.ROLE_SETUP, WIZARD_STEPS.PERMISSION_SETUP, WIZARD_STEPS.REVIEW_CONFIRM].map((step) => {
                 const stepLabels: Record<number, string> = {
                   [WIZARD_STEPS.ROLE_SETUP]: tWizard('roleSetup'),
                   [WIZARD_STEPS.PERMISSION_SETUP]: tWizard('permissionSetup'),
                   [WIZARD_STEPS.REVIEW_CONFIRM]: `${tCommon('review')} & ${tCommon('confirm')}`,
                 };

                 return (
                <div key={step} className="flex items-center">
                  <div className="relative flex flex-col items-center">
                    <div
                      className={`flex items-center justify-center w-10 h-10 rounded-full border-2 z-10 bg-white ${
                        step === wizardState.step
                          ? 'border-primary text-primary font-bold'
                          : step < wizardState.step
                          ? 'bg-green-500 text-white border-green-500'
                          : 'border-gray-200 text-gray-400'
                      }`}
                    >
                      {step < wizardState.step ? (
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          className="h-6 w-6"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={3}
                            d="M5 13l4 4L19 7"
                          />
                        </svg>
                      ) : (
                        step
                      )}
                    </div>
                    <span className={`absolute top-full mt-2 text-xs font-medium whitespace-nowrap ${
                       step === wizardState.step ? 'text-primary' : 'text-gray-500'
                    }`}>
                      {stepLabels[step]}
                    </span>
                  </div>
                  {step < WIZARD_STEPS.REVIEW_CONFIRM && (
                    <div
                      className={`w-32 h-1 mx-2 ${
                        step < wizardState.step ? 'bg-green-500' : 'bg-gray-200'
                      }`}
                    />
                  )}
                </div>
              );
              })}
            </div>


          </div>

          <div className="flex flex-col flex-1 overflow-hidden min-h-0">

            <div className="flex-1 overflow-y-auto scroll-smooth min-h-0">
              {renderStepContent()}
            </div>
          </div>

          {/* Footer buttons */}
          <div className="flex justify-between items-center pt-6 border-t mt-auto shrink-0">
            <Button
              type="button"
              variant="outline"
              onClick={handleCancel}
              disabled={isSubmitting || isActionProcessing}
            >
              {tCommon('cancel')}
            </Button>

            <div className="flex gap-2">
              {!isFirstStep && (
                <Button
                  type="button"
                  variant="outline"
                  onClick={handlePrev}
                  disabled={isSubmitting || isActionProcessing}
                >
                  {tCommon('back')}
                </Button>
              )}

              {!isLastStep && wizardState.step === WIZARD_STEPS.PERMISSION_SETUP && (
                <Button
                  type="button"
                  variant="ghost"
                  onClick={handleSkipToReview}
                  disabled={isSubmitting || isActionProcessing}
                >
                  {tWizard('skip')}
                </Button>
              )}

              {!isLastStep && (
                <Button
                  type="button"
                  onClick={handleNext}
                  disabled={isSubmitting || isActionProcessing || !isStep1Valid}
                >
                  {tCommon('next')}
                </Button>
              )}

              {isLastStep && (
                <Button
                  type="submit"
                  onClick={handleSubmit}
                  disabled={isSubmitting || isActionProcessing}
                >
                  {isSubmitting || isActionProcessing
                    ? isEdit
                      ? tCommon('updating')
                      : tCommon('creating')
                    : isEdit
                    ? tCommon('update')
                    : tCommon('create')}
                </Button>
              )}
            </div>
          </div>
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
