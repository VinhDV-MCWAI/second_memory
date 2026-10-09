import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { adminDisplayName, adminInitials, Header } from './header';
import type { User } from '@/shared/types';

const logout = vi.fn(() => Promise.resolve());
const qaOwner = {
  id: 7,
  user_name: 'qa_owner',
  first_name: 'Quality',
  last_name: 'Owner',
  role: 'owner',
} as User;

vi.mock('next-intl', () => ({ useTranslations: () => (key: string) => key }));
vi.mock('next-themes', () => ({ useTheme: () => ({ theme: 'light', setTheme: vi.fn() }) }));
vi.mock('next/navigation', () => ({ useRouter: () => ({ push: vi.fn() }) }));
vi.mock('@/providers/use-auth', () => ({ useAuth: () => ({ user: qaOwner, logout }) }));

describe('admin name helpers', () => {
  it('uses first and last name, else the user name', () => {
    expect(adminDisplayName(qaOwner)).toBe('Quality Owner');
    expect(adminDisplayName({ ...qaOwner, first_name: '', last_name: '' })).toBe('qa_owner');
  });

  it('makes up to two initials', () => {
    expect(adminInitials(qaOwner)).toBe('QO');
    expect(adminInitials({ ...qaOwner, first_name: '', last_name: '' })).toBe('Q');
  });
});

describe('Header', () => {
  it('shows the signed-in admin, loads no external avatar and has no notification bell', () => {
    const { container } = render(<Header />);

    screen.getByText('Quality Owner');
    screen.getByText('QO');
    expect(screen.queryByText('Vinh Dv')).toBeNull();
    expect(container.querySelector('img')).toBeNull();
    expect(screen.queryByRole('button', { name: /notifications/i })).toBeNull();
  });

  it('logs out from the user menu', async () => {
    render(<Header />);

    fireEvent.keyDown(screen.getByRole('button', { name: 'userMenu' }), { key: 'Enter' });
    await screen.findByText('@qa_owner · owner');
    fireEvent.click(screen.getByRole('menuitem', { name: /logout/ }));

    expect(logout).toHaveBeenCalledOnce();
  });
});
