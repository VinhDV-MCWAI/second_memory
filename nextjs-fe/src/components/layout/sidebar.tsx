"use client";

import React, { useState, useMemo, useCallback } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/shared/utils";
import { Button } from "@/components/ui/button";
import { useAuth } from "@/shared/hooks";
import { LogOut, Menu, X, ChevronDown } from "lucide-react";
import { NAVIGATION_MENU, isRouteActive } from "@/shared/config/navigation";
import { useTranslations } from 'next-intl';

export function Sidebar() {
  const pathname = usePathname();
  const { logout } = useAuth();
  const [isOpen, setIsOpen] = useState(false);
  const [expandedMenu, setExpandedMenu] = useState<string | null>(null);
  const tCommon = useTranslations('common');
  const tNav = useTranslations('navigation');
  const tEntities = useTranslations('entities');

  const toggleMenu = useCallback((label: string) => {
    setExpandedMenu((prev) => prev === label ? null : label);
  }, []);

  const closeSidebar = () => setIsOpen(false);

  const handleLogout = async () => {
    await logout();
  };

  const menuContent = useMemo(
    () =>
      NAVIGATION_MENU.map((item) => (
        <div key={item.label}>
          {item.children ? (
            <>
              <button
                onClick={() => toggleMenu(item.label)}
                className={cn(
                  "w-full flex items-center justify-between rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200",
                  expandedMenu === item.label
                    ? "bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white"
                    : "text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
                )}
                aria-expanded={expandedMenu === item.label}
                aria-label={`Toggle ${item.label} menu`}
              >
                <span className="flex items-center gap-3">
                  <item.icon className="h-5 w-5" />
                  {item.label.startsWith('navigation.') ? tNav(item.label.replace('navigation.', '')) : tEntities(item.label.replace('entities.', ''))}
                </span>
                <ChevronDown
                  className={cn(
                    "h-4 w-4 transition-transform duration-200",
                    expandedMenu === item.label && "rotate-180"
                  )}
                />
              </button>
              <div
                className={cn(
                  "overflow-hidden transition-all duration-200",
                  expandedMenu === item.label ? "mt-1 max-h-96" : "max-h-0"
                )}
              >
                <div className="space-y-1 pl-8">
                  {item.children.map((child) => (
                    <Link
                      key={child.href}
                      href={child.href!}
                      onClick={closeSidebar}
                      className={cn(
                        "block rounded-lg px-4 py-2 text-sm transition-all duration-150",
                        isRouteActive(pathname, child.href)
                          ? "bg-blue-50 font-medium text-blue-600 dark:bg-blue-900/20 dark:text-blue-400"
                          : "text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
                      )}
                    >
                      {child.label.startsWith('navigation.') ? tNav(child.label.replace('navigation.', '')) : tEntities(child.label.replace('entities.', ''))}
                    </Link>
                  ))}
                </div>
              </div>
            </>
          ) : (
            <Link
              href={item.href!}
              onClick={closeSidebar}
              className={cn(
                "flex items-center gap-3 rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-150",
                isRouteActive(pathname, item.href)
                  ? "bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400"
                  : "text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
              )}
            >
              <item.icon className="h-5 w-5" />
              {item.label.startsWith('navigation.') ? tNav(item.label.replace('navigation.', '')) : tEntities(item.label.replace('entities.', ''))}
              {item.badge && (
                <span className="ml-auto rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                  {item.badge}
                </span>
              )}
            </Link>
          )}
        </div>
      )),
    [pathname, expandedMenu, toggleMenu, tNav, tEntities]
  );

  return (
    <>
      {/* Mobile Toggle Button */}
      <Button
        variant="ghost"
        size="icon"
        className="fixed left-4 top-4 z-50 lg:hidden"
        onClick={() => setIsOpen(!isOpen)}
        aria-label={isOpen ? "Close sidebar" : "Open sidebar"}
      >
        {isOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
      </Button>

      {/* Sidebar */}
      <aside
        className={cn(
          "fixed left-0 top-0 z-40 h-screen w-64 border-r border-slate-200 bg-white transition-transform duration-300 dark:border-slate-800 dark:bg-slate-950 lg:z-30 lg:translate-x-0",
          isOpen ? "translate-x-0" : "-translate-x-full"
        )}
        role="navigation"
        aria-label="Main navigation"
      >
        {/* Logo/Brand */}
        <div className="flex items-center justify-center border-b border-slate-200 px-6 py-6 dark:border-slate-800">
          <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
            {tCommon('admin')}
          </h1>
        </div>

        {/* Navigation Menu */}
        <nav className="h-[calc(100vh-5rem-4.5rem)] overflow-y-auto p-4">
          <div className="space-y-2">{menuContent}</div>
        </nav>

        {/* Logout Button */}
        <div className="absolute bottom-0 left-0 right-0 border-t border-slate-200 p-4 dark:border-slate-800">
          <Button
            variant="outline"
            className="w-full justify-start gap-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950 dark:hover:text-red-400"
            onClick={handleLogout}
          >
            <LogOut className="h-5 w-5" />
            {tCommon('logout')}
          </Button>
        </div>
      </aside>

      {/* Mobile Overlay */}
      {isOpen && (
        <div
          className="fixed inset-0 z-30 bg-black/50 backdrop-blur-sm transition-opacity lg:hidden"
          onClick={closeSidebar}
          aria-hidden="true"
        />
      )}
    </>
  );
}
