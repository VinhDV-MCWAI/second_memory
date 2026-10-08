'use client';

import { Controller, useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { FormField } from '@/components/common/form-field';
import { OptionSelect } from '@/components/common/option-select';
import type { ResourceFormProps } from '@/components/common/resource-list-page';
import { HistoryViewer } from '@/features/history/components/history-viewer';
import { useApiData } from '@/shared/hooks/use-api-data';
import { useCrud } from '@/shared/hooks/use-crud';
import { API_ENDPOINTS } from '@/shared/api';
import { GoalStatus, GoalStatusLabels, SkillLevel, SkillLevelLabels } from '@/shared/enums';
import { enumOptions } from '@/shared/utils/enum-options';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { getLearningGoalSchema, type LearningGoalFormData } from '@/shared/validation/validation';
import type { LearningGoal, Skill } from '@/shared/types/models';

/** audit_log.auditable_type of goals (LearningGoalService::$auditableType). */
const AUDITABLE_TYPE = 'learning_goal';

/** The ledger has a few dozen skills; one page fills the select. */
const SKILL_PAGE_SIZE = 100;

/** Status can be set by hand only between open and dropped; `achieved` comes from a level change. */
const MANUAL_STATUSES = [GoalStatus.OPEN, GoalStatus.DROPPED].map((value) => ({
  value,
  label: GoalStatusLabels[value],
}));

/**
 * Learning goal (REQ-002 US-6). The skill of a goal is fixed once created; the API rejects a
 * target the skill already reached, and an achieved goal changes only its date and note.
 */
export function LearningGoalForm({
  initialData,
  onSuccess,
  onCancel,
}: ResourceFormProps<LearningGoal>) {
  const tCommon = useTranslations('common');
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const tLedger = useTranslations('ledger');
  const isEdit = !!initialData;
  const achieved = initialData?.status === GoalStatus.ACHIEVED;
  const { create, update, loading } = useCrud(API_ENDPOINTS.LEDGER.LEARNING_GOAL);
  const { data: skills } = useApiData<Skill>(API_ENDPOINTS.LEDGER.SKILL, {
    per_page: SKILL_PAGE_SIZE,
    enabled: !isEdit,
  });

  const {
    register,
    handleSubmit,
    control,
    setError,
    formState: { errors },
  } = useForm<LearningGoalFormData>({
    resolver: zodResolver(getLearningGoalSchema(tValidation)),
    values: {
      skill_id: initialData?.skill.id ?? 0,
      target_level: (initialData?.target_level as SkillLevel | undefined) ?? SkillLevel.INDEPENDENT,
      target_date: initialData?.target_date ?? '',
      status: (initialData?.status as GoalStatus | undefined) ?? GoalStatus.OPEN,
      note: initialData?.note ?? '',
    },
  });

  const onSubmit = async ({ skill_id, status, target_date, ...data }: LearningGoalFormData) => {
    const fields = { ...data, target_date: target_date || null };
    try {
      if (initialData) {
        // Unchanged values of an achieved goal are accepted, so the whole form can be sent
        await update(initialData.id, achieved ? fields : { ...fields, status });
      } else {
        await create({ ...fields, skill_id });
      }
      onSuccess();
    } catch (error: unknown) {
      handleBindErrors(error, setError);
    }
  };

  return (
    <>
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        {achieved && <p className="text-sm text-muted-foreground">{tLedger('achievedHint')}</p>}

        <FormField id="skill_id" label={tLabels('skill')} required error={errors.skill_id?.message}>
          {isEdit ? (
            <Input id="skill_id" value={initialData.skill.name} disabled readOnly />
          ) : (
            <Controller
              control={control}
              name="skill_id"
              render={({ field }) => (
                <OptionSelect
                  value={field.value || undefined}
                  onChange={(value) => field.onChange(Number(value))}
                  options={skills.map((skill) => ({ value: skill.id, label: skill.name }))}
                />
              )}
            />
          )}
        </FormField>

        <div className="grid grid-cols-2 gap-4">
          <FormField
            id="target_level"
            label={tLabels('targetLevel')}
            required
            error={errors.target_level?.message}
          >
            <Controller
              control={control}
              name="target_level"
              render={({ field }) => (
                <fieldset disabled={achieved}>
                  <OptionSelect
                    value={field.value}
                    onChange={(value) => field.onChange(Number(value) as SkillLevel)}
                    options={enumOptions(SkillLevelLabels)}
                  />
                </fieldset>
              )}
            />
          </FormField>
          <FormField
            id="target_date"
            label={tLabels('targetDate')}
            error={errors.target_date?.message}
          >
            <Input id="target_date" type="date" {...register('target_date')} />
          </FormField>
        </div>

        {isEdit && !achieved && (
          <FormField id="status" label={tLabels('status')} error={errors.status?.message}>
            <Controller
              control={control}
              name="status"
              render={({ field }) => (
                <OptionSelect
                  value={field.value}
                  onChange={(value) => field.onChange(value as GoalStatus)}
                  options={MANUAL_STATUSES}
                />
              )}
            />
          </FormField>
        )}

        <FormField id="note" label={tLabels('note')} error={errors.note?.message}>
          <Textarea id="note" rows={3} {...register('note')} />
        </FormField>

        <div className="flex justify-end gap-2 pt-4">
          <Button type="button" variant="outline" onClick={onCancel} disabled={loading}>
            {tCommon('cancel')}
          </Button>
          <Button type="submit" disabled={loading}>
            {isEdit ? tCommon('update') : tCommon('create')}
          </Button>
        </div>
      </form>
      {initialData && (
        <HistoryViewer auditableType={AUDITABLE_TYPE} recordId={initialData.id} className="mt-6" />
      )}
    </>
  );
}
