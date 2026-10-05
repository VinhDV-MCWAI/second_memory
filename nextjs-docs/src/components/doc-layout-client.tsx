"use client";

import { useState } from "react";
import { Menu, X } from "lucide-react";
import LeftSidebar from "@/components/left-sidebar";
import { Entry } from "@/types/docs";
import SearchBar from "@/components/search-bar";
import ThemeToggle from "@/components/theme-toggle";
import Breadcrumbs from "@/components/breadcrumbs";
import BackToTop from "@/components/back-to-top";

interface DocLayoutClientProps {
  children: React.ReactNode;
  entries: Entry[];
  categorySlug: string;
  layoutStructure?: any;
}

export default function DocLayoutClient({
  children,
  entries,
  categorySlug,
  layoutStructure,
}: DocLayoutClientProps) {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  return (
    <div className="min-h-screen flex flex-col bg-background text-foreground transition-colors duration-300">
      {/* Fixed Header / Action Bar */}
      <header className="sticky top-0 z-40 w-full border-b bg-background/80 backdrop-blur-md">
        <div className="flex h-16 items-center justify-between px-4 md:px-8">
          {/* Mobile Menu Toggle */}
          <button
            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            className="p-2 mr-2 text-muted-foreground hover:text-foreground md:hidden"
          >
            {isMobileMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
          </button>

          {/* Breadcrumbs - Left Side */}
          <div className="hidden md:block flex-1 min-w-0 mr-4">
            <Breadcrumbs entries={entries} categorySlug={categorySlug} />
          </div>

          {/* Actions - Center/Right */}
          <div className="flex items-center gap-4 flex-1 md:flex-initial justify-end">
            <div className="w-full max-w-[250px] lg:max-w-[320px]">
              <SearchBar />
            </div>
            <ThemeToggle />
          </div>
        </div>
        
        {/* Mobile Breadcrumbs Sub-header */}
        <div className="md:hidden px-4 py-2 border-t border-border/50 bg-muted/30">
          <Breadcrumbs entries={entries} categorySlug={categorySlug} />
        </div>
      </header>

      <div className="flex w-full min-h-[calc(100vh-4rem)] relative">
        {/* Left Sidebar - Sticky with isolated scroll */}
        <aside className="hidden md:block w-72 lg:w-80 sticky top-16 h-[calc(100vh-4rem)] overflow-y-auto overscroll-contain scrollbar-hide shrink-0 px-2 border-r border-transparent">
          <div className="py-6">
            <LeftSidebar entries={entries} categorySlug={categorySlug} layoutStructure={layoutStructure} />
          </div>
        </aside>

        {/* Mobile Sidebar Overlay */}
        {isMobileMenuOpen && (
          <div
            className="fixed inset-0 z-50 bg-background/80 backdrop-blur-sm md:hidden"
            onClick={() => setIsMobileMenuOpen(false)}
          >
            <div
              className="fixed inset-y-0 left-0 z-50 w-72 bg-background p-6 shadow-xl animate-in slide-in-from-left duration-300"
              onClick={(e) => e.stopPropagation()}
            >
              <div className="flex items-center justify-between mb-8">
                <span className="font-bold text-xl">Menu</span>
                <button onClick={() => setIsMobileMenuOpen(false)}>
                  <X className="h-6 w-6" />
                </button>
              </div>
              <div className="h-[calc(100vh-8rem)] overflow-y-auto scrollbar-hide">
                <LeftSidebar entries={entries} categorySlug={categorySlug} layoutStructure={layoutStructure} />
              </div>
            </div>
          </div>
        )}

        {/* Main Content Area */}
        <main className="flex-1 min-w-0">
          {children}
        </main>
      </div>

      {/* Floating Back to Top Button */}
      <BackToTop />
    </div>
  );
}
