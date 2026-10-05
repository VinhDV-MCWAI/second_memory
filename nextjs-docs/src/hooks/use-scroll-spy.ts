import { useEffect, useState, useRef } from "react";

interface UseScrollSpyOptions {
  rootMargin?: string;
  threshold?: number | number[];
}

export function useScrollSpy(
  ids: string[],
  options: UseScrollSpyOptions = {}
): string | null {
  const [activeId, setActiveId] = useState<string | null>(null);
  
  // Use a ref to store all current intersections to avoid closure issues
  const visibleItems = useRef<Set<string>>(new Set());

  useEffect(() => {
    if (ids.length === 0) return;

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            visibleItems.current.add(entry.target.id);
          } else {
            visibleItems.current.delete(entry.target.id);
          }
        });

        // Find the "best" active ID from the visible ones
        // We pick the first one that appears in the 'ids' array order
        const firstVisible = ids.find(id => visibleItems.current.has(id));
        if (firstVisible) {
          setActiveId(firstVisible);
        }
      },
      {
        rootMargin: options.rootMargin || "-100px 0px -80% 0px",
        threshold: options.threshold || 0,
      }
    );

    ids.forEach((id) => {
      const el = document.getElementById(id);
      if (el) observer.observe(el);
    });

    return () => {
      observer.disconnect();
    };
  }, [ids, options.rootMargin, options.threshold]);

  return activeId;
}
