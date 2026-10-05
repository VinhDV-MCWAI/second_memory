'use client';

import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Category } from "@/types/docs";
import SearchBar from "@/components/search-bar";
import HexagonGrid from "@/components/hexagon-grid";

export default function DocsPage() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const loadCategories = async () => {
      try {
        const data = await api.getCategories() as Category[];
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
      <div className="min-h-screen flex items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen relative overflow-x-hidden flex flex-col items-center bg-[#f8fbff] bg-gradient-to-br from-blue-50/70 via-white to-purple-50/70">
      {/* 
        The top bar / search section.
        Fixed at the top, centered horizontally, and floating above content.
      */}
      <div className="fixed top-10 left-1/2 -translate-x-1/2 z-50 w-full max-w-2xl px-6">
        <SearchBar />
      </div>

      {/* Hexagon Grid Container - spans full width */}
      {/* Added pt-32 to push the grid down so it's not hidden behind the fixed search bar */}
      <div className="w-full flex-1 relative z-10 pt-32 pb-10 overflow-clip">
        <HexagonGrid categories={categories} />
      </div>
    </div>
  );
}
