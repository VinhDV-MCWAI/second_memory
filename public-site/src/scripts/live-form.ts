// Checks an end-user form while the visitor types (ADR-0020). The rules are the inputs' own
// attributes (required, minlength, maxlength, pattern), kept equal to the API's; on top of them
// `data-match="<name>"` wants the value of another field (password again) and
// `data-differ="<name>"` a different one (new password). A field's message shows under it once it
// is left, then on every change; the submit button stays off until every field passes.
import { ApiError, showErrors } from '@/scripts/user-api';

const MESSAGES = {
  required: 'Required',
  tooShort: (min: number) => `At least ${min} characters`,
  tooLong: (max: number) => `At most ${max} characters`,
  match: 'Does not match',
  differ: 'Must differ from the current one',
};

type Field = HTMLInputElement;

export interface LiveForm {
  /** The API's 422 messages go under their fields until those change; the rest in the box. */
  showServerErrors(error: unknown): void;
}

export function liveForm(form: HTMLFormElement): LiveForm {
  const fields = [...form.querySelectorAll<Field>('input[name]')];
  const submit = form.querySelector<HTMLButtonElement>('button[type="submit"]');
  const box = form.querySelector<HTMLElement>('[data-errors]');
  const touched = new Set<Field>();
  const serverError = new Map<Field, string>();

  const other = (name: string | undefined) =>
    name ? form.querySelector<Field>(`input[name="${name}"]`) : null;

  const ruleMessage = (field: Field): string => {
    const { validity } = field;
    if (validity.valueMissing) return MESSAGES.required;
    if (validity.tooShort) return MESSAGES.tooShort(field.minLength);
    if (validity.tooLong) return MESSAGES.tooLong(field.maxLength);
    if (validity.patternMismatch) return field.title;
    const match = other(field.dataset.match);
    if (match && field.value !== match.value) return MESSAGES.match;
    const differ = other(field.dataset.differ);
    if (differ && field.value !== '' && field.value === differ.value) return MESSAGES.differ;
    return serverError.get(field) ?? '';
  };

  const messageOf = (field: Field) => {
    let node = form.querySelector<HTMLElement>(`[data-field-error="${field.name}"]`);
    if (!node) {
      node = Object.assign(document.createElement('p'), {
        id: `${form.id}-${field.name}-error`,
        className: 'mt-1 text-sm text-red-600',
      });
      node.dataset.fieldError = field.name;
      // After the label, so the message is not part of the field's name
      (field.closest('label') ?? field).after(node);
      field.setAttribute('aria-describedby', node.id);
    }
    return node;
  };

  const refresh = () => {
    for (const field of fields) {
      field.setCustomValidity('');
      const message = ruleMessage(field);
      field.setCustomValidity(message);
      const shown = touched.has(field) ? message : '';
      messageOf(field).textContent = shown;
      field.toggleAttribute('aria-invalid', shown !== '');
    }
    if (submit) submit.disabled = !form.checkValidity();
  };

  for (const field of fields) {
    field.addEventListener('blur', () => {
      touched.add(field);
      refresh();
    });
    field.addEventListener('input', () => {
      serverError.delete(field);
      refresh();
    });
  }
  form.addEventListener('reset', () => {
    touched.clear();
    serverError.clear();
    // The fields are emptied after this event
    queueMicrotask(refresh);
  });
  refresh();

  return {
    showServerErrors(error) {
      if (
        !(error instanceof ApiError) ||
        error.messages === null ||
        typeof error.messages === 'string'
      ) {
        if (box) showErrors(box, error);
        return;
      }
      const rest: Record<string, string[]> = {};
      for (const [name, lines] of Object.entries(error.messages)) {
        const field = other(name);
        if (field && fields.includes(field)) {
          serverError.set(field, lines.join(' '));
          touched.add(field);
        } else {
          rest[name] = lines;
        }
      }
      if (box) {
        box.hidden = true;
        if (Object.keys(rest).length > 0) showErrors(box, new ApiError(error.status, rest));
      }
      refresh();
    },
  };
}
