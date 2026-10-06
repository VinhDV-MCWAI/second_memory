'use client';

import { useEffect, useState } from 'react';
import { api } from '@/lib/api';
import { Category } from '@/types/docs';
import SearchBar from '@/components/search-bar';
import HexagonGrid from '@/components/hexagon-grid';

export default function DocsPage() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const loadCategories = async () => {
      try {
        const data = (await api.getCategories()) as Category[];
        setCategories(data);
      } catch (error) {
        console.error('Failed to load categories:', error);
      } finally {
        setIsLoading(false);
      }
    };

    loadCategories();
  }, []);

  if (isLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
      </div>
    );
  }

  return (
    <div className="relative flex min-h-screen flex-col items-center overflow-x-hidden bg-[#f8fbff] bg-gradient-to-br from-blue-50/70 via-white to-purple-50/70">
      {/* 
        The top bar / search section.
        Fixed at the top, centered horizontally, and floating above content.
      */}
      <div className="fixed top-10 left-1/2 z-50 w-full max-w-2xl -translate-x-1/2 px-6">
        <SearchBar />
      </div>

      {/* Hexagon Grid Container - spans full width */}
      {/* Added pt-32 to push the grid down so it's not hidden behind the fixed search bar */}
      <div className="relative z-10 w-full flex-1 overflow-clip pt-32 pb-10">
        <HexagonGrid categories={categories} />
      </div>
    </div>
  );
}
