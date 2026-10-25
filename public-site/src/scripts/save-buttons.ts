// Marks and toggles the ☆ / 🔖 buttons of SaveButtons.astro (ADR-0020, ADR-0024). A guest who
// clicks one goes to the sign-in page and comes back here afterwards.
import { onEveryPage } from '@/scripts/every-page';
import {
  api,
  forgetSaved,
  loginUrl,
  maybeSignedIn,
  savedItems,
  type SavedKind,
} from '@/scripts/user-api';

const buttonsOfPage = () => [...document.querySelectorAll<HTMLButtonElement>('button[data-save]')];

/** What a button or saved item stands for: kind, article or category, public key or slug */
const idOf = (kind: string, type: string, key: string) => `${kind}|${type}|${key}`;

function setPressed(button: HTMLButtonElement, pressed: boolean): void {
  button.setAttribute('aria-pressed', String(pressed));
  button.textContent = (pressed ? button.dataset.iconOn : button.dataset.icon) ?? '';
}

async function markSaved(buttons: HTMLButtonElement[]): Promise<void> {
  if (!maybeSignedIn() || buttons.length === 0) return;
  const saved = await savedItems().catch(() => []);
  const ids = new Set(saved.map((item) => idOf(item.kind, item.type, item.key)));
  for (const button of buttons) {
    const { save, type, key } = button.dataset;
    setPressed(button, ids.has(idOf(save ?? '', type ?? '', key ?? '')));
  }
}

async function toggle(button: HTMLButtonElement): Promise<void> {
  if (!maybeSignedIn()) {
    location.href = loginUrl();
    return;
  }
  const pressed = button.getAttribute('aria-pressed') === 'true';
  const body = {
    kind: button.dataset.save as SavedKind,
    [button.dataset.type === 'category' ? 'category' : 'article']: button.dataset.key,
  };
  button.disabled = true;
  try {
    await api('POST', pressed ? 'user/saved/delete' : 'user/saved/store', body);
    forgetSaved();
    setPressed(button, !pressed);
  } catch {
    if (!maybeSignedIn()) location.href = loginUrl();
  } finally {
    button.disabled = false;
  }
}

onEveryPage(() => {
  const buttons = buttonsOfPage();
  for (const button of buttons) {
    button.addEventListener('click', () => void toggle(button));
  }
  void markSaved(buttons);
});
