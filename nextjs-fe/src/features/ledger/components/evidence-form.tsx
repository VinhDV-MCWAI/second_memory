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
import { IdPicker } from '@/features/ledger/components/id-picker';
import { useCrud } from '@/shared/hooks/use-crud';
import { API_ENDPOINTS } from '@/shared/api';
import { EvidenceSource, EvidenceType, EvidenceTypeLabels } from '@/shared/enums';
import { enumOptions } from '@/shared/utils/enum-options';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { getEvidenceSchema, type EvidenceFormData } from '@/shared/validation/validation';
import type { Evidence } from '@/shared/types/models';

/** audit_log.auditable_type of evidence (EvidenceService::$auditableType). */
const AUDITABLE_TYPE = 'evidence';

/**
 * Evidence link for one or more skills (REQ-002 US-2). Rows imported from Obsidian keep
 * their vault-owned fields read-only: the importer would overwrite them (RFC-002 §4.5).
 */
export function EvidenceForm({ initialData, onSuccess, onCancel }: ResourceFormProps<Evidence>) {
  const tCommon = useTranslations('common');
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const tLedger = useTranslations('ledger');
  const imported = initialData?.source === EvidenceSource.OBSIDIAN;
  const { create, update, loading } = useCrud(API_ENDPOINTS.LEDGER.EVIDENCE);

  const {
    register,
    handleSubmit,
    control,
    setError,
    formState: { errors },
  } = useForm<EvidenceFormData>({
    resolver: zodResolver(getEvidenceSchema(tValidation)),
    values: {
      type: (initialData?.type as EvidenceType | undefined) ?? EvidenceType.PR,
      title: initialData?.title ?? '',
      url: initialData?.url ?? '',
      occurred_on: initialData?.occurred_on ?? '',
      summary: initialData?.summary ?? '',
      is_public: initialData?.is_public ?? false,
      skill_ids: initialData?.skills?.map((skill) => skill.id) ?? [],
      tag_ids: initialData?.tags?.map((tag) => tag.id) ?? [],
    },
  });

  const onSubmit = async (data: EvidenceFormData) => {
    try {
      if (initialData) {
        await update(initialData.id, data);
      } else {
        await create(data);
      }
      onSuccess();
    } catch (error: unknown) {
      handleBindErrors(error, setError);
    }
  };

  return (
    <>
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        {imported && <p className="text-sm text-muted-foreground">{tLedger('importedHint')}</p>}

        <div className="grid grid-cols-3 gap-4">
          <FormField id="type" label={tLabels('type')} required error={errors.type?.message}>
            <Controller
              control={control}
              name="type"
              render={({ field }) => (
                <OptionSelect
                  value={field.value}
                  onChange={(value) => field.onChange(value as EvidenceType)}
                  options={enumOptions(EvidenceTypeLabels)}
                />
              )}
            />
          </FormField>
          <div className="col-span-2">
            <FormField id="title" label={tLabels('title')} required error={errors.title?.message}>
              <Input id="title" disabled={imported} {...register('title')} />
            </FormField>
          </div>
        </div>

        <div className="grid grid-cols-3 gap-4">
          <div className="col-span-2">
            <FormField id="url" label={tLabels('url')} required error={errors.url?.message}>
              <Input id="url" type="url" disabled={imported} {...register('url')} />
            </FormField>
          </div>
          <FormField
            id="occurred_on"
            label={tLabels('occurredOn')}
            required
            error={errors.occurred_on?.message}
          >
            <Input id="occurred_on" type="date" disabled={imported} {...register('occurred_on')} />
          </FormField>
        </div>

        <FormField id="summary" label={tLabels('summary')} error={errors.summary?.message}>
          <Textarea id="summary" rows={3} disabled={imported} {...register('summary')} />
        </FormField>

        <FormField
          id="skill_ids"
          label={tLabels('skills')}
          required
          error={errors.skill_ids?.message}
        >
          <fieldset disabled={imported}>
            <Controller
              control={control}
              name="skill_ids"
              render={({ field }) => (
                <IdPicker
                  endpoint={API_ENDPOINTS.LEDGER.SKILL}
                  emptyMessage={tLedger('noSkills')}
                  value={field.value}
                  onChange={field.onChange}
                />
              )}
            />
          </fieldset>
        </FormField>

        <FormField id="tag_ids" label={tLabels('tags')} error={errors.tag_ids?.message}>
          <fieldset disabled={imported}>
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
          </fieldset>
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

        <div className="flex justify-end gap-2 pt-4">
          <Button type="button" variant="outline" onClick={onCancel} disabled={loading}>
            {tCommon('cancel')}
          </Button>
          <Button type="submit" disabled={loading}>
            {initialData ? tCommon('update') : tCommon('create')}
          </Button>
        </div>
      </form>
      {initialData && (
        <HistoryViewer auditableType={AUDITABLE_TYPE} recordId={initialData.id} className="mt-6" />
      )}
    </>
  );
}
