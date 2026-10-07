'use client';

import * as React from 'react';
import { Moon, Sun, Monitor } from 'lucide-react';
import { useTheme } from 'next-themes';
import { cn } from '@/lib/utils';

export default function ThemeToggle() {
  const { theme, setTheme } = useTheme();
  const [mounted, setMounted] = React.useState(false);

  // Avoid hydration mismatch
  React.useEffect(() => {
    setMounted(true);
  }, []);

  if (!mounted) {
    return <div className="h-10 w-10 lg:h-9 lg:w-9" />;
  }

  return (
    <div className="flex items-center gap-1 rounded-lg border border-border/50 bg-accent/50 p-1">
      <button
        onClick={() => setTheme('light')}
        className={cn(
          'rounded-md p-1.5 transition-all',
          theme === 'light'
            ? 'bg-background text-foreground shadow-sm'
            : 'text-muted-foreground hover:bg-accent hover:text-foreground',
        )}
        title="Light Mode"
      >
        <Sun className="h-4 w-4" />
      </button>
      <button
        onClick={() => setTheme('dark')}
        className={cn(
          'rounded-md p-1.5 transition-all',
          theme === 'dark'
            ? 'bg-background text-foreground shadow-sm'
            : 'text-muted-foreground hover:bg-accent hover:text-foreground',
        )}
        title="Dark Mode"
      >
        <Moon className="h-4 w-4" />
      </button>
      <button
        onClick={() => setTheme('system')}
        className={cn(
          'rounded-md p-1.5 transition-all',
          theme === 'system'
            ? 'bg-background text-foreground shadow-sm'
            : 'text-muted-foreground hover:bg-accent hover:text-foreground',
        )}
        title="System Mode"
      >
        <Monitor className="h-4 w-4" />
      </button>
    </div>
  );
}
