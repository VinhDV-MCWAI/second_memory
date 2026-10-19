// Marks and toggles the ☆ / 🔖 buttons of SaveButtons.astro (ADR-0020). A guest who clicks one
// goes to the sign-in page and comes back here afterwards.
import { api, loginUrl, maybeSignedIn, savedItems, type SavedKind } from '@/scripts/user-api';

const buttons = [...document.querySelectorAll<HTMLButtonElement>('button[data-save]')];

type Target = 'skill' | 'evidence' | 'article';

/** What a button saves: its data-skill (slug), data-evidence or data-article (public key). */
function target(button: HTMLButtonElement): [Target, string] {
  const { skill, article, evidence } = button.dataset;
  if (skill) return ['skill', skill];
  return article ? ['article', article] : ['evidence', evidence ?? ''];
}

function setPressed(button: HTMLButtonElement, pressed: boolean): void {
  button.setAttribute('aria-pressed', String(pressed));
  button.textContent = (pressed ? button.dataset.iconOn : button.dataset.icon) ?? '';
}

async function markSaved(): Promise<void> {
  if (!maybeSignedIn() || buttons.length === 0) return;
  const saved = await savedItems().catch(() => []);
  const keys = new Set(saved.map((item) => `${item.kind}|${item.target}|${item.key}`));
  for (const button of buttons) {
    const [type, key] = target(button);
    setPressed(button, keys.has(`${button.dataset.save}|${type}|${key}`));
  }
}

for (const button of buttons) {
  button.addEventListener('click', async () => {
    if (!maybeSignedIn()) {
      location.href = loginUrl();
      return;
    }
    const pressed = button.getAttribute('aria-pressed') === 'true';
    const [type, key] = target(button);
    const body = { kind: button.dataset.save as SavedKind, [type]: key };
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
