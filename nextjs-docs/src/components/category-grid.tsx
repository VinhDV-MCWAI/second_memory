"use client";

import { Category } from "@/types/docs";
import Link from "next/link";

interface HexagonGridProps {
  categories: Category[];
}

export default function HexagonGrid({ categories }: HexagonGridProps) {
  // Sort categories by rank_order
  const sortedCategories = [...categories].sort(
    (a, b) => a.rank_order - b.rank_order
  );

  return (
    <div style={{
      display: "flex",
      flexWrap: "wrap",
      justifyContent: "center",
      gap: "1.5rem",
      maxWidth: "80rem",
      margin: "0 auto"
    }}>
      {sortedCategories.map((category, index) => {
        const colorIndex = index % 6;
        const colors = [
          { bg: "rgba(59, 130, 246, 0.1)", border: "#60a5fa", text: "#2563eb" },
          { bg: "rgba(168, 85, 247, 0.1)", border: "#a78bfa", text: "#7c3aed" },
          { bg: "rgba(236, 72, 153, 0.1)", border: "#f472b6", text: "#db2777" },
          { bg: "rgba(34, 197, 94, 0.1)", border: "#4ade80", text: "#16a34a" },
          { bg: "rgba(249, 115, 22, 0.1)", border: "#fb923c", text: "#ea580c" },
          { bg: "rgba(6, 182, 212, 0.1)", border: "#22d3ee", text: "#0891b2" },
        ];
        const color = colors[colorIndex];
        
        // Create varying widths for asymmetric layout
        const widthVariants = ["14rem", "16rem", "12rem", "15rem", "13rem", "17rem"];
        const heightVariants = ["12rem", "10rem", "11rem", "13rem", "10.5rem", "11.5rem"];
        const width = widthVariants[index % widthVariants.length];
        const height = heightVariants[index % heightVariants.length];

        return (
          <Link
            key={category.id}
            href={`/docs/${category.slug}`}
            style={{
              position: "relative",
              width: width,
              height: height,
              textDecoration: "none",
              color: "inherit",
              margin: "0.5rem"
            }}
          >
            <div
              style={{
                width: "100%",
                height: "100%",
                position: "relative",
                transition: "all 0.3s ease-out",
                clipPath: "polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%)",
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.transform = "scale(1.05)";
                e.currentTarget.style.zIndex = "10";
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.transform = "scale(1)";
                e.currentTarget.style.zIndex = "1";
              }}
            >
              <div
                style={{
                  width: "100%",
                  height: "100%",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  padding: "1.5rem 1.25rem",
                  border: `2px solid ${color.border}`,
                  backgroundColor: color.bg,
                  transition: "all 0.3s"
                }}
              >
                <h3 style={{
                  fontSize: "1rem",
                  fontWeight: "600",
                  textAlign: "center",
                  lineHeight: "1.4",
                  color: color.text
                }}>
                  {category.name}
                </h3>
              </div>
            </div>
          </Link>
        );
      })}
    </div>
  );
}
