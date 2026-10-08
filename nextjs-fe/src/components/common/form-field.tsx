import type { ReactNode } from 'react';
import { Label } from '@/components/ui/label';

interface FormFieldProps {
  /** id of the control, used by the label. */
  id: string;
  label: ReactNode;
  required?: boolean;
  /** Validation message shown under the control. */
  error?: string;
  children: ReactNode;
}

/** Label (with a required mark), control and validation message of one form field. */
export function FormField({ id, label, required, error, children }: FormFieldProps) {
  return (
    <div className="space-y-2">
      <Label htmlFor={id}>
        {label}
        {required && (
          <>
            {' '}
            <span className="text-red-500">*</span>
          </>
        )}
      </Label>
      {children}
      {error && <p className="text-sm text-red-500">{error}</p>}
    </div>
  );
}
