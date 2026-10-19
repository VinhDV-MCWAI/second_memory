// Top-right link of every page: "Sign in", or the signed-in user's name to their lists (ADR-0020).
import { ROUTES } from '@/lib/routes';
import { currentUser } from '@/scripts/user-api';

void (async () => {
  const link = document.querySelector<HTMLAnchorElement>('a[data-account]');
  const user = await currentUser();
  if (link && user) {
    link.textContent = `${user.display_name} · My lists`;
    link.href = ROUTES.ME;
  }
})();
