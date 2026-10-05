'use client';

import { ArrowUp } from 'lucide-react';
import { cn } from "@/shared/utils";
import { Button } from '@/components/ui/button';
import { useEffect, useState } from 'react';
import { UI_CONSTANTS } from '@/shared/config';
import type { ContentProps } from '@/shared/types/ui.types';

export function Content({ 
  children, 
  className, 
  padded = true,
  fullWidth = false 
}: ContentProps) {
  const [showScrollTop, setShowScrollTop] = useState(false);

  useEffect(() => {
    const handleScroll = (e: Event) => {
      const target = e.target as HTMLElement;
      setShowScrollTop(target.scrollTop > UI_CONSTANTS.SCROLL_TOP_THRESHOLD);
    };

    const mainElement = document.querySelector('main');
    mainElement?.addEventListener('scroll', handleScroll);

    return () => {
      mainElement?.removeEventListener('scroll', handleScroll);
    };
  }, []);

  const scrollToTop = () => {
    const mainElement = document.querySelector('main');
    mainElement?.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <main
      className={cn(
        'relative flex-1 overflow-auto bg-slate-50 dark:bg-slate-900',
        padded && 'p-4 lg:p-6',
        className
      )}
    >
      <div
        className={cn(
          'mx-auto',
          !fullWidth && 'max-w-screen-2xl'
        )}
      >
        {children}
      </div>

      {/* Scroll to Top Button */}
      {showScrollTop && (
        <Button
          size="icon"
          className="fixed bottom-8 right-8 z-10 h-12 w-12 rounded-full shadow-lg transition-all hover:scale-110"
          onClick={scrollToTop}
          aria-label="Scroll to top"
        >
          <ArrowUp className="h-5 w-5" />
        </Button>
      )}
    </main>
  );
}
