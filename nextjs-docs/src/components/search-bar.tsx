'use client';

import { useState, useCallback } from 'react';
import { Search, X } from 'lucide-react';
import { useRouter } from 'next/navigation';
import { debounce } from '@/lib/utils';
import { api } from '@/lib/api';
import { SearchResult, Category, Entry } from '@/types/docs';

export default function SearchBar() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<SearchResult | null>(null);
  const [isSearching, setIsSearching] = useState(false);
  const [showResults, setShowResults] = useState(false);
  const router = useRouter();

  const clearSearch = () => {
    setQuery('');
    setResults(null);
    setShowResults(false);
  };

  const performSearch = useCallback(
    debounce(async (searchQuery: string) => {
      if (searchQuery.trim().length < 2) {
        setResults(null);
        setShowResults(false);
        return;
      }

      setIsSearching(true);
      try {
        const data = await api.search(searchQuery);
        setResults(data as SearchResult);
        setShowResults(true);
      } catch (error) {
        console.error('Search failed:', error);
      } finally {
        setIsSearching(false);
      }
    }, 300),
    [],
  );

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setQuery(value);
    performSearch(value);
  };

  const handleResultClick = (categorySlug: string, entrySlug: string) => {
    router.push(`/docs/${categorySlug}/${entrySlug}`);
    setShowResults(false);
    setQuery('');
  };

  return (
    <div className="relative z-10 w-full">
      <div className="group flex h-11 w-full cursor-text items-center rounded-full border-none bg-[#d1d1d1] px-4 py-2 text-sm shadow-md transition-all focus-within:bg-[#e0e0e0]">
        <Search className="mr-3 h-4 w-4 shrink-0 text-white transition-colors" />
        <input
          type="text"
          placeholder="Search documentation..."
          value={query}
          onChange={handleInputChange}
          className="w-full flex-1 border-none bg-transparent text-[15px] text-white outline-none placeholder:text-white focus:ring-0 focus:outline-none"
        />
        {query && (
          <button
            type="button"
            onClick={clearSearch}
            className="ml-2 rounded-full p-1 transition-colors hover:bg-white/10"
          >
            <X className="h-4 w-4 text-white hover:text-white/80" />
          </button>
        )}
      </div>

      {showResults && results && (
        <div className="absolute z-50 mt-2 max-h-[500px] w-full overflow-y-auto rounded-lg border border-border bg-popover shadow-lg">
          {/* Categories */}
          {results.categories && results.categories.length > 0 && (
            <div className="border-b p-4">
              <h3 className="mb-2 text-sm font-semibold text-muted-foreground">Categories</h3>
              {results.categories.map((cat: Category) => (
                <div
                  key={cat.id}
                  onClick={() => router.push(`/docs/${cat.slug}`)}
                  className="cursor-pointer rounded p-2 hover:bg-accent"
                >
                  <div className="font-medium">{cat.name}</div>
                  {cat.description && (
                    <div className="text-sm text-muted-foreground">{cat.description}</div>
                  )}
                </div>
              ))}
            </div>
          )}

          {/* Entries */}
          {results.entries && results.entries.length > 0 && (
            <div className="border-b p-4">
              <h3 className="mb-2 text-sm font-semibold text-muted-foreground">Entries</h3>
              {results.entries.map((entry: Entry) => (
                <div key={entry.id} className="cursor-pointer rounded p-2 hover:bg-accent">
                  <div className="font-medium">{entry.name}</div>
                </div>
              ))}
            </div>
          )}

          {/* Descriptions */}
          {results.descriptions && results.descriptions.length > 0 && (
            <div className="p-4">
              <h3 className="mb-2 text-sm font-semibold text-muted-foreground">Content</h3>
              {results.descriptions.map((desc) => (
                <div
                  key={desc.id}
                  onClick={() => handleResultClick(desc.entry.slug || '', desc.entry.slug)}
                  className="cursor-pointer rounded p-2 hover:bg-accent"
                >
                  <div className="font-medium">{desc.title}</div>
                  <div className="line-clamp-2 text-sm text-muted-foreground">{desc.summary}</div>
                </div>
              ))}
            </div>
          )}

          {!isSearching &&
            results.categories?.length === 0 &&
            results.entries?.length === 0 &&
            results.descriptions?.length === 0 && (
              <div className="p-8 text-center text-muted-foreground">No results found</div>
            )}
        </div>
      )}
    </div>
  );
}
