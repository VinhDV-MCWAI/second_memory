// Marks and toggles the ☆ / 🔖 buttons of SaveButtons.astro (ADR-0020). A guest who clicks one
// goes to the sign-in page and comes back here afterwards.
import { api, loginUrl, maybeSignedIn, savedItems, type SavedKind } from '@/scripts/user-api';

const buttons = [...document.querySelectorAll<HTMLButtonElement>('button[data-save]')];

/** The article a button saves: its data-article (public key). */
const articleOf = (button: HTMLButtonElement): string => button.dataset.article ?? '';

function setPressed(button: HTMLButtonElement, pressed: boolean): void {
  button.setAttribute('aria-pressed', String(pressed));
  button.textContent = (pressed ? button.dataset.iconOn : button.dataset.icon) ?? '';
}

async function markSaved(): Promise<void> {
  if (!maybeSignedIn() || buttons.length === 0) return;
  const saved = await savedItems().catch(() => []);
  const keys = new Set(saved.map((item) => `${item.kind}|${item.key}`));
  for (const button of buttons) {
    setPressed(button, keys.has(`${button.dataset.save}|${articleOf(button)}`));
  }
}

for (const button of buttons) {
  button.addEventListener('click', async () => {
    if (!maybeSignedIn()) {
      location.href = loginUrl();
      return;
    }
    const pressed = button.getAttribute('aria-pressed') === 'true';
    const body = { kind: button.dataset.save as SavedKind, article: articleOf(button) };
    button.disabled = true;
    try {
      await api('POST', pressed ? 'user/saved/delete' : 'user/saved/store', body);
      setPressed(button, !pressed);
    } catch {
      if (!maybeSignedIn()) location.href = loginUrl();
    } finally {
      button.disabled = false;
    }
  });
}

void markSaved();
