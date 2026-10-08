'use client';

import { Controller, useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { FormField } from '@/components/common/form-field';
import { OptionSelect } from '@/components/common/option-select';
import type { ResourceFormProps } from '@/components/common/resource-list-page';
import { HistoryViewer } from '@/features/history/components/history-viewer';
import { SkillLevelTimeline } from '@/features/ledger/components/skill-level-timeline';
import { IdPicker } from '@/features/ledger/components/id-picker';
import { useCrud } from '@/shared/hooks/use-crud';
import { API_ENDPOINTS } from '@/shared/api';
import { SkillLevel, SkillLevelLabels } from '@/shared/enums';
import { enumOptions } from '@/shared/utils/enum-options';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { getSkillSchema, type SkillFormData } from '@/shared/validation/validation';
import type { Skill } from '@/shared/types/models';

/** audit_log.auditable_type of skills (SkillService::$auditableType). */
const AUDITABLE_TYPE = 'skill';

const LEVEL_FIELDS = ['level', 'changed_on', 'reason'] as const;

/**
 * Create: the skill with its first level entry. Edit: the skill's own fields; its level
 * changes only through the timeline below, so the history stays complete (REQ-002 US-1).
 */
export function SkillForm({ initialData, onSuccess, onCancel }: ResourceFormProps<Skill>) {
  const tCommon = useTranslations('common');
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const tLedger = useTranslations('ledger');
  const isEdit = !!initialData;
  const { create, update, loading } = useCrud(API_ENDPOINTS.LEDGER.SKILL, {
    invalidateKeys: [API_ENDPOINTS.LEDGER.SKILL, API_ENDPOINTS.LEDGER.SKILL_LEVEL],
  });

  const {
    register,
    handleSubmit,
    control,
    setError,
    formState: { errors },
  } = useForm<SkillFormData>({
    resolver: zodResolver(getSkillSchema(tValidation)),
    values: {
      name: initialData?.name ?? '',
      category: initialData?.category ?? '',
      description: initialData?.description ?? '',
      is_public: initialData?.is_public ?? false,
      tag_ids: initialData?.tags?.map((tag) => tag.id) ?? [],
      level: (initialData?.current_level as SkillLevel | undefined) ?? SkillLevel.LEARNING,
      changed_on: '',
      reason: '',
    },
  });

  const onSubmit = async ({ changed_on, ...data }: SkillFormData) => {
    try {
      if (initialData) {
        const skill = Object.fromEntries(
          Object.entries(data).filter(
            ([field]) => !(LEVEL_FIELDS as readonly string[]).includes(field),
          ),
        );
        await update(initialData.id, skill);
      } else {
        // An empty date means today on the server
        await create(changed_on ? { ...data, changed_on } : data);
      }
      onSuccess();
    } catch (error: unknown) {
      handleBindErrors(error, setError);
    }
  };

  return (
    <>
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        <div className="grid grid-cols-2 gap-4">
          <FormField id="name" label={tLabels('name')} required error={errors.name?.message}>
            <Input id="name" {...register('name')} aria-invalid={!!errors.name} />
          </FormField>
          <FormField
            id="category"
            label={tLabels('category')}
            required
            error={errors.category?.message}
          >
            <Input id="category" {...register('category')} aria-invalid={!!errors.category} />
          </FormField>
        </div>

        <FormField
          id="description"
          label={tLabels('description')}
          error={errors.description?.message}
        >
          <Textarea id="description" rows={3} {...register('description')} />
        </FormField>

        <FormField id="tag_ids" label={tLabels('tags')} error={errors.tag_ids?.message}>
          <Controller
            control={control}
            name="tag_ids"
            render={({ field }) => (
              <IdPicker
                endpoint={API_ENDPOINTS.LEDGER.TAG}
                emptyMessage={tLedger('noTags')}
                value={field.value}
                onChange={field.onChange}
              />
            )}
          />
        </FormField>

        <Controller
          control={control}
          name="is_public"
          render={({ field }) => (
            <div className="flex items-center gap-2">
              <Checkbox
                id="is_public"
                checked={field.value}
                onCheckedChange={(checked) => field.onChange(checked === true)}
              />
              <Label htmlFor="is_public">{tLabels('isPublic')}</Label>
            </div>
          )}
        />

        {!isEdit && (
          <div className="grid grid-cols-2 gap-4">
            <FormField id="level" label={tLabels('level')} required error={errors.level?.message}>
              <Controller
                control={control}
                name="level"
                render={({ field }) => (
                  <OptionSelect
                    value={field.value}
                    onChange={(value) => field.onChange(Number(value) as SkillLevel)}
                    options={enumOptions(SkillLevelLabels)}
                  />
                )}
              />
            </FormField>
            <FormField
              id="changed_on"
              label={tLabels('changedOn')}
              error={errors.changed_on?.message}
            >
              <Input id="changed_on" type="date" {...register('changed_on')} />
            </FormField>
            <div className="col-span-2">
              <FormField id="reason" label={tLabels('reason')} error={errors.reason?.message}>
                <Input id="reason" {...register('reason')} />
              </FormField>
            </div>
          </div>
        )}

        <div className="flex justify-end gap-2 pt-4">
          <Button type="button" variant="outline" onClick={onCancel} disabled={loading}>
            {tCommon('cancel')}
          </Button>
          <Button type="submit" disabled={loading}>
            {isEdit ? tCommon('update') : tCommon('create')}
          </Button>
        </div>
      </form>
      {isEdit && initialData && (
        <>
          <SkillLevelTimeline skillId={initialData.id} className="mt-6" />
          <HistoryViewer
            auditableType={AUDITABLE_TYPE}
            recordId={initialData.id}
            className="mt-6"
          />
        </>
      )}
    </>
  );
}
