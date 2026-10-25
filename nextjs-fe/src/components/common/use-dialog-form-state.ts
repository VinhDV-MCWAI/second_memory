'use client';

import { useEffect } from 'react';
import type { FieldValues, UseFormReturn } from 'react-hook-form';
import type { ResourceFormState } from '@/components/common/resource-list-page';

interface DialogFormStateOptions {
  pending: boolean;
  /** Editing a saved record: its fields are checked at once, so a rule it breaks shows. */
  editing: boolean;
  onStateChange: (state: ResourceFormState) => void;
  /** Nothing may be saved whatever the fields say (e.g. the owner role). */
  blocked?: boolean;
}

/**
 * Tells the create/edit dialog about an entity form: saving, changed, and whether the save
 * button is on (only while every field passes). Pair it with `mode: FORM_VALIDATION_MODE`.
 */
export function useDialogFormState<T extends FieldValues>(
  { formState, trigger }: UseFormReturn<T>,
  { pending, editing, onStateChange, blocked = false }: DialogFormStateOptions,
) {
  const { isDirty, isValid } = formState;

  useEffect(
    () => onStateChange({ pending, dirty: isDirty, submitDisabled: blocked || !isValid }),
    [pending, isDirty, isValid, blocked, onStateChange],
  );

  useEffect(() => {
    if (editing) void trigger();
  }, [editing, trigger]);
}
