"use client";

import { Category } from "@/types/docs";
import Link from "next/link";
import { useMemo, useState, useEffect } from "react";

interface HexagonGridProps {
  categories: Category[];
}

// Left-to-right, top-to-bottom grid mapping
function generateGridCoords(itemCount: number, cols: number) {
  const coords = [];
  // Start placing items at row 1, col 1 (to leave a 1x1 gap from the search bar / top-left)
  let r = 1;
  let c = 1;

  for (let i = 0; i < itemCount; i++) {
    coords.push({ r, c, index: i });
    c++;
    
    const rowOffset = (r % 2 !== 0) ? 1 : 0; // odd rows have 1 less hex
    if (c >= cols - rowOffset) {
      r++;
      c = 0; // Or c=1 if we always want a left gap, but c=0 allows wider bottom rows
    }
  }
  
  return coords;
}

const HEX_WIDTH = 156;
const HEX_HEIGHT = 180;
const HEX_POINTS = "78,0 156,45 156,135 78,180 0,135 0,45";

// Background grid coverage
const BG_START_R = -4;
const BG_START_C = -6;
const BG_END_C = 12;

export default function HexagonGrid({ categories }: HexagonGridProps) {
  const [cols, setCols] = useState(6); // Default server-side to 6 columns
  const [mounted, setMounted] = useState(false);

  // Dynamic D-Flex style wrapping by tracking window size and adjusting max columns
  useEffect(() => {
    setMounted(true);
    const handleResize = () => {
      const w = window.innerWidth;
      if (w < 480) setCols(2);
      else if (w < 768) setCols(3);
      else if (w < 1024) setCols(4);
      else if (w < 1280) setCols(5);
      else setCols(6);
    };
    handleResize(); // Init on mount
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  const sortedCategories = [...categories].sort((a, b) => a.rank_order - b.rank_order);
  
  const gridData = useMemo(() => {
    const coords = generateGridCoords(sortedCategories.length, cols);
    const assigned = new Map();
    
    // Determine colors with a glowing style compatible with Tailwind standard palettes
    const getGlowColor = (index: number) => {
      const colors = [
        { text: "#2563eb", glow: "rgba(37, 99, 235, 0.45)" },     // blue
        { text: "#ec4899", glow: "rgba(236, 72, 153, 0.5)" },    // pink
        { text: "#ea580c", glow: "rgba(234, 88, 12, 0.5)" },    // orange
        { text: "#16a34a", glow: "rgba(22, 163, 74, 0.5)" },     // green
        { text: "#9333ea", glow: "rgba(147, 51, 234, 0.5)" },    // purple
        { text: "#0891b2", glow: "rgba(8, 145, 178, 0.5)" },     // cyan
        { text: "#ca8a04", glow: "rgba(202, 138, 4, 0.5)" },     // yellow
      ];
      return colors[index % colors.length];
    };

    let maxRow = 0;

    sortedCategories.forEach((cat, idx) => {
      const coord = coords[idx];
      if (coord) {
         maxRow = Math.max(maxRow, coord.r);
         assigned.set(`${coord.r},${coord.c}`, { 
           category: cat,
           color: getGlowColor(idx),
           isCenter: idx === 0 
         });
      }
    });

    return { assigned, maxRow };
  }, [sortedCategories, cols]); // Recompute grid coordinates when `cols` breakpoint changes

  // Calculate dynamic container height purely based on data rows (+1 padding at bottom)
  const containerHeight = Math.max(
    0, 
    ((gridData.maxRow + 2) * HEX_HEIGHT * 0.75) + (HEX_HEIGHT * 0.25)
  );

  return (
    <div className="relative w-full overflow-hidden flex justify-center pb-20 px-4 min-h-[500px]">
      {/* 
        This wrapper holds the grid. Its width scales with the column count.
        Using transition-all here ensures that when you resize the window and columns change,
        the hexagons smoothly animate/wrap to their new target positions!
      */}
      <div 
        className="relative transition-all duration-700 ease-in-out" 
        style={{ 
           height: `${containerHeight}px`, 
           width: `${cols * HEX_WIDTH + HEX_WIDTH}px`,
           marginTop: '2rem',
           opacity: mounted ? 1 : 0 // Avoid server mismatch visual flutter
        }}
      >
        {/* Render fully transparent Background Grid fading outwards */}
        {(() => {
          const bgCells = [];
          
          // Compute enough background rows to cover any standard screen height 
          const currentBgEndR = Math.max(12, gridData.maxRow + 3); 

          for (let r = BG_START_R; r <= currentBgEndR; r++) {
            for (let c = BG_START_C; c <= BG_END_C; c++) {
              const isOddRow = r % 2 !== 0;
              const left = c * HEX_WIDTH + (isOddRow ? HEX_WIDTH / 2 : 0);
              const top = r * (HEX_HEIGHT * 0.75);

              // Calculate fade opacity based on distance from the data center
              const centerR = Math.floor(gridData.maxRow / 2);
              const centerC = Math.floor(cols / 2);
              const dist = Math.sqrt(Math.pow(r - centerR, 2) + Math.pow(c - centerC, 2));
              const opacity = Math.max(0.01, 1 - (dist * 0.18));

              bgCells.push(
                <div
                  key={`bg-${r}-${c}`}
                  className="absolute z-0 pointer-events-none transition-all duration-700"
                  style={{
                    left: `${left}px`,
                    top: `${top}px`,
                    width: `${HEX_WIDTH}px`,
                    height: `${HEX_HEIGHT}px`,
                    opacity: opacity
                  }}
                >
                  <svg
                    className="w-full h-full overflow-visible"
                    viewBox={`0 0 ${HEX_WIDTH} ${HEX_HEIGHT}`}
                  >
                    <polygon
                      points={HEX_POINTS}
                      fill="transparent"
                      stroke="#cbd5e1" // Slate-300
                      strokeWidth="0.8"
                    />
                    <circle cx="78" cy="0" r="2.5" fill="#bae6fd" />
                    <circle cx="156" cy="45" r="2.5" fill="#bae6fd" />
                    <circle cx="156" cy="135" r="2.5" fill="#bae6fd" />
                    <circle cx="78" cy="180" r="2.5" fill="#bae6fd" />
                    <circle cx="0" cy="135" r="2.5" fill="#bae6fd" />
                    <circle cx="0" cy="45" r="2.5" fill="#bae6fd" />
                  </svg>
                </div>
              );
            }
          }
          return bgCells;
        })()}

        {/* Render overlay Active Hexagons */}
        {Array.from(gridData.assigned.entries()).map(([key, data]) => {
          const [rStr, cStr] = key.split(",");
          const r = parseInt(rStr);
          const c = parseInt(cStr);
          
          const isOddRow = r % 2 !== 0;
          const left = c * HEX_WIDTH + (isOddRow ? HEX_WIDTH / 2 : 0);
          const top = r * (HEX_HEIGHT * 0.75);

          return (
            <Link
              key={`active-${data.category.id}`}
              href={`/docs/${data.category.slug}`}
              title={data.category.name} // Native HTML Tooltip on Hover
              className="absolute z-10 group block transition-all duration-700 ease-in-out hover:z-30"
              style={{
                left: `${left}px`,
                top: `${top}px`,
                width: `${HEX_WIDTH}px`,
                height: `${HEX_HEIGHT}px`,
              }}
            >
              <svg
                className="absolute inset-0 w-full h-full overflow-visible transition-transform duration-300 group-hover:scale-110"
                viewBox={`0 0 ${HEX_WIDTH} ${HEX_HEIGHT}`}
                style={{ filter: `drop-shadow(0 0 16px ${data.color.glow})` }}
              >
                <polygon
                  points={HEX_POINTS}
                  fill="rgba(255, 255, 255, 0.96)"
                  stroke={data.color.text}
                  strokeWidth="2.5"
                  className="transition-all duration-300 group-hover:stroke-[4px]"
                />
                <polygon
                  points={HEX_POINTS}
                  fill={data.color.text}
                  opacity="0.0"
                  className="transition-opacity group-hover:opacity-10"
                />
              </svg>

              <div className="absolute inset-0 flex flex-col items-center justify-center p-3 pointer-events-none transition-transform duration-300 group-hover:scale-110">
                <div className="w-[85%] text-center px-1 max-w-[120px]"> {/* Fixed width to force text-wrapping within Hexagon safe bounds */}
                  <h3 
                    className="font-extrabold uppercase leading-[1.2] line-clamp-2 overflow-hidden text-ellipsis m-0"
                    style={{
                      color: data.color.text,
                      fontSize: data.isCenter ? "1.2rem" : "0.95rem",
                    }}
                  >
                    {data.category.name}
                  </h3>
                </div>
              </div>
            </Link>
          );
        })}
      </div>
    </div>
  );
}
