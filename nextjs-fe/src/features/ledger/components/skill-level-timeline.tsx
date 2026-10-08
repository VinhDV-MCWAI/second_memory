'use client';

import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { TrendingUp } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { FormField } from '@/components/common/form-field';
import { OptionSelect } from '@/components/common/option-select';
import { useApiData } from '@/shared/hooks/use-api-data';
import { useCrud } from '@/shared/hooks/use-crud';
import { API_ENDPOINTS } from '@/shared/api';
import { SkillLevel, SkillLevelLabels } from '@/shared/enums';
import { enumOptions } from '@/shared/utils/enum-options';
import { handleBindErrors } from '@/shared/utils/error-handler';
import { getSkillLevelSchema, type SkillLevelFormData } from '@/shared/validation/validation';
import type { SkillLevelEntry } from '@/shared/types/models';

/** A skill's history is short; one page shows all of it. */
const HISTORY_PAGE_SIZE = 100;

interface SkillLevelTimelineProps {
  skillId: number;
  className?: string;
}

/** Append-only level history of one skill, newest first, with a form to record a change. */
export function SkillLevelTimeline({ skillId, className }: SkillLevelTimelineProps) {
  const t = useTranslations('ledger.levels');
  const tLabels = useTranslations('forms.labels');
  const tValidation = useTranslations('validation');
  const { data: entries } = useApiData<SkillLevelEntry>(API_ENDPOINTS.LEDGER.SKILL_LEVEL, {
    filters: { skill_id: skillId },
    per_page: HISTORY_PAGE_SIZE,
  });
  const { create, loading } = useCrud(API_ENDPOINTS.LEDGER.SKILL_LEVEL, {
    invalidateKeys: [API_ENDPOINTS.LEDGER.SKILL_LEVEL, API_ENDPOINTS.LEDGER.SKILL],
    messages: { create: t('recorded') },
  });

  const {
    register,
    handleSubmit,
    control,
    reset,
    setError,
    formState: { errors },
  } = useForm<SkillLevelFormData>({
    resolver: zodResolver(getSkillLevelSchema(tValidation)),
    defaultValues: { level: SkillLevel.LEARNING, changed_on: '', reason: '' },
  });

  const onSubmit = async ({ changed_on, ...data }: SkillLevelFormData) => {
    try {
      // An empty date means today on the server
      await create({ ...data, skill_id: skillId, ...(changed_on ? { changed_on } : {}) });
      reset();
    } catch (error: unknown) {
      handleBindErrors(error, setError);
    }
  };

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <TrendingUp className="h-5 w-5" />
          {t('title')}
        </CardTitle>
        <CardDescription>{t('description')}</CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        <form onSubmit={handleSubmit(onSubmit)} className="grid grid-cols-3 items-end gap-3">
          <FormField id="new_level" label={tLabels('level')} error={errors.level?.message}>
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
            id="new_changed_on"
            label={tLabels('changedOn')}
            error={errors.changed_on?.message}
          >
            <Input id="new_changed_on" type="date" {...register('changed_on')} />
          </FormField>
          <FormField id="new_reason" label={tLabels('reason')} error={errors.reason?.message}>
            <Input id="new_reason" {...register('reason')} />
          </FormField>
          <div className="col-span-3 flex justify-end">
            <Button type="submit" disabled={loading}>
              {loading ? t('recording') : t('record')}
            </Button>
          </div>
        </form>

        {entries.length === 0 ? (
          <p className="text-sm text-muted-foreground">{t('empty')}</p>
        ) : (
          <ol className="space-y-3 border-l pl-4">
            {entries.map((entry) => (
              <li key={entry.id}>
                <div className="flex flex-wrap items-center gap-2">
                  <Badge>{entry.level_label}</Badge>
                  <span className="text-sm">{entry.changed_on}</span>
                  {entry.recorded_by_user_name && (
                    <span className="text-xs text-muted-foreground">
                      {t('by', { name: entry.recorded_by_user_name })}
                    </span>
                  )}
                </div>
                {entry.reason && (
                  <p className="mt-1 text-sm text-muted-foreground">{entry.reason}</p>
                )}
              </li>
            ))}
          </ol>
        )}
      </CardContent>
    </Card>
  );
}
