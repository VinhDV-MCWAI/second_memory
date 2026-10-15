'use client';

import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { AppDialog } from '@/components/common/app-dialog';
import { FormField } from '@/components/common/form-field';
import { Input } from '@/components/ui/input';
import { handleBindErrors } from '@/shared/utils/error-handler';

const FORM_ID = 'media-field-form';

interface MediaFieldDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  /** Request field the value is sent as; server errors on it show under the input. */
  field: 'name' | 'folder';
  label: string;
  hint?: string;
  initialValue: string;
  submitLabel: string;
  schema: z.ZodString;
  onSubmit: (value: string) => Promise<unknown>;
}

/** One-input dialog of the file screen: new folder, rename, move. */
export function MediaFieldDialog({
  open,
  onOpenChange,
  title,
  field,
  label,
  hint,
  initialValue,
  submitLabel,
  schema,
  onSubmit,
}: MediaFieldDialogProps) {
  const form = useForm<{ name: string; folder: string }>({
    resolver: zodResolver(
      z.object({
        name: field === 'name' ? schema : z.string(),
        folder: field === 'folder' ? schema : z.string(),
      }),
    ),
    defaultValues: { name: '', folder: '' },
  });
  const { register, handleSubmit, reset, setError, formState } = form;

  useEffect(() => {
    if (open) reset({ name: '', folder: '', [field]: initialValue });
  }, [open, field, initialValue, reset]);

  const submit = handleSubmit(async (values) => {
    try {
      await onSubmit(values[field].trim());
      onOpenChange(false);
    } catch (error) {
      handleBindErrors(error, setError);
    }
  });

  return (
    <AppDialog
      open={open}
      onOpenChange={onOpenChange}
      title={title}
      size="sm"
      dirty={formState.isDirty}
      actions={[
        { key: 'submit', label: submitLabel, form: FORM_ID, disabled: formState.isSubmitting },
      ]}
    >
      <form id={FORM_ID} onSubmit={submit} noValidate>
        <FormField id="media-field" label={label} required error={formState.errors[field]?.message}>
          <Input id="media-field" autoFocus {...register(field)} />
        </FormField>
        {hint && <p className="mt-2 text-xs text-muted-foreground">{hint}</p>}
      </form>
    </AppDialog>
  );
}
