'use client';

import { useTranslations } from 'next-intl';
import { Button } from '@/components/ui/button';
import { WIZARD_STEPS } from '@/features/roles/role-wizard.constant';

interface WizardFooterProps {
  step: number;
  isEdit: boolean;
  busy: boolean;
  /** Step 1 is filled in (name and permission). */
  canProceed: boolean;
  onCancel: () => void;
  onPrev: () => void;
  onSkip: () => void;
  onNext: () => void;
  onSubmit: () => void;
}

export function WizardFooter({
  step,
  isEdit,
  busy,
  canProceed,
  onCancel,
  onPrev,
  onSkip,
  onNext,
  onSubmit,
}: WizardFooterProps) {
  const tCommon = useTranslations('common');
  const tWizard = useTranslations('roleWizard');
  const isLastStep = step === WIZARD_STEPS.REVIEW_CONFIRM;
  const isFirstStep = step === WIZARD_STEPS.ROLE_SETUP;

  return (
    <div className="mt-auto flex shrink-0 items-center justify-between border-t pt-6">
      <Button type="button" variant="outline" onClick={onCancel} disabled={busy}>
        {tCommon('cancel')}
      </Button>

      <div className="flex gap-2">
        {!isFirstStep && (
          <Button type="button" variant="outline" onClick={onPrev} disabled={busy}>
            {tCommon('back')}
          </Button>
        )}

        {!isLastStep && step === WIZARD_STEPS.PERMISSION_SETUP && (
          <Button type="button" variant="ghost" onClick={onSkip} disabled={busy}>
            {tWizard('skip')}
          </Button>
        )}

        {!isLastStep && (
          <Button type="button" onClick={onNext} disabled={busy || !canProceed}>
            {tCommon('next')}
          </Button>
        )}

        {isLastStep && (
          <Button type="submit" onClick={onSubmit} disabled={busy}>
            {busy
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
  );
}
