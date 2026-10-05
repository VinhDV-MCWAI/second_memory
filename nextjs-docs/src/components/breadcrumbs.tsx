"use client"

import * as React from "react"
import Link from "next/link"
import { usePathname } from "next/navigation"
import { ChevronRight, Home } from "lucide-react"
import { Entry } from "@/types/docs"

interface BreadcrumbsProps {
  entries: Entry[];
  categorySlug: string;
}

export default function Breadcrumbs({ entries, categorySlug }: BreadcrumbsProps) {
  const pathname = usePathname()
  const segments = pathname.split("/").filter(Boolean)
  
  // Find the current entry name
  const currentEntrySlug = segments[segments.length - 1]
  const currentEntry = entries.find(e => e.slug === currentEntrySlug)
  
  // Format category name (e.g. getting-started -> Getting Started)
  const categoryName = categorySlug
    .split("-")
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ")

  return (
    <nav className="flex items-center gap-2 text-sm text-muted-foreground overflow-x-auto whitespace-nowrap scrollbar-hide py-1">
      <Link 
        href="/docs" 
        className="flex items-center gap-1 hover:text-foreground transition-colors"
      >
        <Home className="h-3.5 w-3.5" />
        <span>Docs</span>
      </Link>
      
      <ChevronRight className="h-3.5 w-3.5 shrink-0 Opacity-50" />
      
      <Link 
        href={`/docs/${categorySlug}`}
        className="hover:text-foreground transition-colors max-w-[120px] truncate"
      >
        {categoryName}
      </Link>
      
      {currentEntry && (
        <>
          <ChevronRight className="h-3.5 w-3.5 shrink-0 Opacity-50" />
          <span className="text-foreground font-medium max-w-[200px] truncate">
            {currentEntry.name}
          </span>
        </>
      )}
    </nav>
  )
}
