"use client";

import { useState, useCallback } from "react";
import { Search, X } from "lucide-react";
import { useRouter } from "next/navigation";
import { debounce } from "@/lib/utils";
import { api } from "@/lib/api";
import { SearchResult, Category, Entry } from "@/types/docs";

export default function SearchBar() {
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<SearchResult | null>(null);
  const [isSearching, setIsSearching] = useState(false);
  const [showResults, setShowResults] = useState(false);
  const router = useRouter();
  
  const clearSearch = () => {
    setQuery("");
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
        console.error("Search failed:", error);
      } finally {
        setIsSearching(false);
      }
    }, 300),
    []
  );

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setQuery(value);
    performSearch(value);
  };

  const handleResultClick = (categorySlug: string, entrySlug: string) => {
    router.push(`/docs/${categorySlug}/${entrySlug}`);
    setShowResults(false);
    setQuery("");
  };

  return (
    <div className="relative w-full z-10">
      <div className="flex items-center w-full h-11 rounded-full bg-[#d1d1d1] px-4 py-2 text-sm shadow-md transition-all focus-within:bg-[#e0e0e0] cursor-text group border-none">
        <Search className="h-4 w-4 text-white mr-3 shrink-0 transition-colors" />
        <input
          type="text"
          placeholder="Search documentation..."
          value={query}
          onChange={handleInputChange}
          className="flex-1 bg-transparent border-none outline-none text-white placeholder:text-white text-[15px] focus:ring-0 focus:outline-none w-full"
        />
        {query && (
          <button 
            type="button" 
            onClick={clearSearch} 
            className="p-1 hover:bg-white/10 rounded-full transition-colors ml-2"
          >
            <X className="h-4 w-4 text-white hover:text-white/80" />
          </button>
        )}
      </div>

      {showResults && results && (
        <div className="absolute z-50 w-full mt-2 bg-popover border border-border rounded-lg shadow-lg max-h-[500px] overflow-y-auto">
          {/* Categories */}
          {results.categories && results.categories.length > 0 && (
            <div className="p-4 border-b">
              <h3 className="text-sm font-semibold text-muted-foreground mb-2">
                Categories
              </h3>
              {results.categories.map((cat: Category) => (
                <div
                  key={cat.id}
                  onClick={() => router.push(`/docs/${cat.slug}`)}
                  className="p-2 hover:bg-accent rounded cursor-pointer"
                >
                  <div className="font-medium">{cat.name}</div>
                  {cat.description && (
                    <div className="text-sm text-muted-foreground">
                      {cat.description}
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}

          {/* Entries */}
          {results.entries && results.entries.length > 0 && (
            <div className="p-4 border-b">
              <h3 className="text-sm font-semibold text-muted-foreground mb-2">
                Entries
              </h3>
              {results.entries.map((entry: Entry) => (
                <div
                  key={entry.id}
                  className="p-2 hover:bg-accent rounded cursor-pointer"
                >
                  <div className="font-medium">{entry.name}</div>
                </div>
              ))}
            </div>
          )}

          {/* Descriptions */}
          {results.descriptions && results.descriptions.length > 0 && (
            <div className="p-4">
              <h3 className="text-sm font-semibold text-muted-foreground mb-2">
                Content
              </h3>
              {results.descriptions.map((desc: any) => (
                <div
                  key={desc.id}
                  onClick={() =>
                    handleResultClick(
                      desc.entry.slug || "",
                      desc.entry.slug
                    )
                  }
                  className="p-2 hover:bg-accent rounded cursor-pointer"
                >
                  <div className="font-medium">{desc.title}</div>
                  <div className="text-sm text-muted-foreground line-clamp-2">
                    {desc.summary}
                  </div>
                </div>
              ))}
            </div>
          )}

          {!isSearching &&
            results.categories?.length === 0 &&
            results.entries?.length === 0 &&
            results.descriptions?.length === 0 && (
              <div className="p-8 text-center text-muted-foreground">
                No results found
              </div>
            )}
        </div>
      )}
    </div>
  );
}
