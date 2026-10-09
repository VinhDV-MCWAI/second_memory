'use client';

import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { useState, useEffect, type FormEvent } from 'react';
import { useRouter } from 'next/navigation';
import { Search, Sun, Moon, LogOut } from 'lucide-react';
import { useTheme } from 'next-themes';
import { useTranslations } from 'next-intl';
import { ADMIN_ROUTES, SEARCH_PARAM, THEME } from '@/shared/config';
import { useAuth } from '@/providers/use-auth';
import type { User } from '@/shared/types';

/** "First Last", or the user name when both are empty. */
export function adminDisplayName(user: User): string {
  return [user.first_name, user.last_name].filter(Boolean).join(' ').trim() || user.user_name;
}

/** Up to two letters for the avatar, from the display name. */
export function adminInitials(user: User): string {
  return adminDisplayName(user)
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('');
}

export function Header() {
  const { theme, setTheme } = useTheme();
  const router = useRouter();
  const [mounted, setMounted] = useState(false);
  const t = useTranslations('header');
  const tCommon = useTranslations('common');

  const { user, logout } = useAuth();
  const displayName = user ? adminDisplayName(user) : '';
  const initials = user ? adminInitials(user) : '';

  // Prevent hydration mismatch for theme
  useEffect(() => {
    const timer = requestAnimationFrame(() => {
      setMounted(true);
    });
    return () => cancelAnimationFrame(timer);
  }, []);

  // Enter opens the search page (REQ-002 US-5)
  const handleSearch = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const q = String(new FormData(event.currentTarget).get(SEARCH_PARAM) ?? '').trim();
    if (q) router.push(`${ADMIN_ROUTES.SEARCH}?${new URLSearchParams({ [SEARCH_PARAM]: q })}`);
  };

  const toggleTheme = () => {
    setTheme(theme === THEME.DARK ? THEME.LIGHT : THEME.DARK);
  };

  return (
    <header className="sticky top-0 z-20 border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
      <div className="flex items-center justify-between gap-4 px-4 py-3 lg:px-6">
        {/* Left Section - Search */}
        <div className="flex flex-1 items-center gap-4">
          <div className="hidden flex-1 md:flex">
            <div className="relative w-full max-w-md">
              <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <form role="search" onSubmit={handleSearch}>
                <Input
                  name={SEARCH_PARAM}
                  placeholder={t('searchPlaceholder')}
                  aria-label={t('searchPlaceholder')}
                  className="pl-10"
                  type="search"
                />
              </form>
            </div>
          </div>
        </div>

        {/* Right Section - Actions */}
        <div className="flex items-center gap-2">
          {/* Search Button (Mobile) */}
          <Button variant="ghost" size="icon" className="md:hidden" aria-label="Search">
            <Search className="h-5 w-5" />
          </Button>

          {/* Dark Mode Toggle */}
          {mounted && (
            <Button
              variant="ghost"
              size="icon"
              onClick={toggleTheme}
              aria-label={`Switch to ${theme === THEME.DARK ? THEME.LIGHT : THEME.DARK} mode`}
              className="transition-transform hover:scale-110"
            >
              {theme === THEME.DARK ? (
                <Sun className="h-5 w-5 text-yellow-500" />
              ) : (
                <Moon className="h-5 w-5 text-slate-700" />
              )}
            </Button>
          )}

          {/* User Menu */}
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="gap-2 rounded-full" aria-label={t('userMenu')}>
                <Avatar className="h-8 w-8">
                  <AvatarFallback>{initials}</AvatarFallback>
                </Avatar>
                <span className="hidden text-sm font-medium sm:inline">{displayName}</span>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
              <DropdownMenuLabel className="font-normal">
                <p className="text-sm font-medium">{displayName}</p>
                {user && (
                  <p className="text-xs text-slate-500 dark:text-slate-400">
                    @{user.user_name} · {user.role}
                  </p>
                )}
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem
                className="text-red-600 focus:text-red-600 dark:text-red-400"
                onSelect={() => void logout()}
              >
                <LogOut className="mr-2 h-4 w-4" />
                {tCommon('logout')}
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </header>
  );
}
