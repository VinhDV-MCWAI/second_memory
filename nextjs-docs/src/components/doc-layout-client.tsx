'use client';

import { useState } from 'react';
import { Menu, X } from 'lucide-react';
import LeftSidebar from '@/components/left-sidebar';
import { Entry } from '@/types/docs';
import SearchBar from '@/components/search-bar';
import ThemeToggle from '@/components/theme-toggle';
import Breadcrumbs from '@/components/breadcrumbs';
import BackToTop from '@/components/back-to-top';
import type { RawLayoutStructure } from '@/lib/layout-structure';

interface DocLayoutClientProps {
  children: React.ReactNode;
  entries: Entry[];
  categorySlug: string;
  layoutStructure?: RawLayoutStructure;
}

export default function DocLayoutClient({
  children,
  entries,
  categorySlug,
  layoutStructure,
}: DocLayoutClientProps) {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  return (
    <div className="flex min-h-screen flex-col bg-background text-foreground transition-colors duration-300">
      {/* Fixed Header / Action Bar */}
      <header className="sticky top-0 z-40 w-full border-b bg-background/80 backdrop-blur-md">
        <div className="flex h-16 items-center justify-between px-4 md:px-8">
          {/* Mobile Menu Toggle */}
          <button
            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            className="mr-2 p-2 text-muted-foreground hover:text-foreground md:hidden"
          >
            {isMobileMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
          </button>

          {/* Breadcrumbs - Left Side */}
          <div className="mr-4 hidden min-w-0 flex-1 md:block">
            <Breadcrumbs entries={entries} categorySlug={categorySlug} />
          </div>

          {/* Actions - Center/Right */}
          <div className="flex flex-1 items-center justify-end gap-4 md:flex-initial">
            <div className="w-full max-w-[250px] lg:max-w-[320px]">
              <SearchBar />
            </div>
            <ThemeToggle />
          </div>
        </div>

        {/* Mobile Breadcrumbs Sub-header */}
        <div className="border-t border-border/50 bg-muted/30 px-4 py-2 md:hidden">
          <Breadcrumbs entries={entries} categorySlug={categorySlug} />
        </div>
      </header>

      <div className="relative flex min-h-[calc(100vh-4rem)] w-full">
        {/* Left Sidebar - Sticky with isolated scroll */}
        <aside className="scrollbar-hide sticky top-16 hidden h-[calc(100vh-4rem)] w-72 shrink-0 overflow-y-auto overscroll-contain border-r border-transparent px-2 md:block lg:w-80">
          <div className="py-6">
            <LeftSidebar
              entries={entries}
              categorySlug={categorySlug}
              layoutStructure={layoutStructure}
            />
          </div>
        </aside>

        {/* Mobile Sidebar Overlay */}
        {isMobileMenuOpen && (
          <div
            className="fixed inset-0 z-50 bg-background/80 backdrop-blur-sm md:hidden"
            onClick={() => setIsMobileMenuOpen(false)}
          >
            <div
              className="animate-in slide-in-from-left fixed inset-y-0 left-0 z-50 w-72 bg-background p-6 shadow-xl duration-300"
              onClick={(e) => e.stopPropagation()}
            >
              <div className="mb-8 flex items-center justify-between">
                <span className="text-xl font-bold">Menu</span>
                <button onClick={() => setIsMobileMenuOpen(false)}>
                  <X className="h-6 w-6" />
                </button>
              </div>
              <div className="scrollbar-hide h-[calc(100vh-8rem)] overflow-y-auto">
                <LeftSidebar
                  entries={entries}
                  categorySlug={categorySlug}
                  layoutStructure={layoutStructure}
                />
              </div>
            </div>
          </div>
        )}

        {/* Main Content Area */}
        <main className="min-w-0 flex-1">{children}</main>
      </div>

      {/* Floating Back to Top Button */}
      <BackToTop />
    </div>
  );
}
