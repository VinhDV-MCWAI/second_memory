'use client';

import { useTranslations } from 'next-intl';
import { WIZARD_STEPS } from '@/features/roles/role-wizard.constant';

const STEPS = [
  WIZARD_STEPS.ROLE_SETUP,
  WIZARD_STEPS.PERMISSION_SETUP,
  WIZARD_STEPS.REVIEW_CONFIRM,
] as const;

export function WizardStepIndicator({ currentStep }: { currentStep: number }) {
  const tCommon = useTranslations('common');
  const tWizard = useTranslations('roleWizard');

  const stepLabels: Record<number, string> = {
    [WIZARD_STEPS.ROLE_SETUP]: tWizard('roleSetup'),
    [WIZARD_STEPS.PERMISSION_SETUP]: tWizard('permissionSetup'),
    [WIZARD_STEPS.REVIEW_CONFIRM]: `${tCommon('review')} & ${tCommon('confirm')}`,
  };

  return (
    <div className="mb-8 flex shrink-0">
      {STEPS.map((step) => (
        <div key={step} className="flex items-center">
          <div className="relative flex flex-col items-center">
            <div
              className={`z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 bg-white ${
                step === currentStep
                  ? 'border-primary font-bold text-primary'
                  : step < currentStep
                    ? 'border-green-500 bg-green-500 text-white'
                    : 'border-gray-200 text-gray-400'
              }`}
            >
              {step < currentStep ? (
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
            <span
              className={`absolute top-full mt-2 text-xs font-medium whitespace-nowrap ${
                step === currentStep ? 'text-primary' : 'text-gray-500'
              }`}
            >
              {stepLabels[step]}
            </span>
          </div>
          {step < WIZARD_STEPS.REVIEW_CONFIRM && (
            <div
              className={`mx-2 h-1 w-32 ${step < currentStep ? 'bg-green-500' : 'bg-gray-200'}`}
            />
          )}
        </div>
      ))}
    </div>
  );
}
