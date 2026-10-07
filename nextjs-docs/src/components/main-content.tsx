'use client';

import { useMemo } from 'react';
import { EntryDetail } from '@/types/docs';
import { slugify } from '@/lib/utils';
import { ContentRenderer } from './content-renderer';
import { parseLayoutStructure, type LayoutNode } from '@/lib/layout-structure';

interface MainContentProps {
  entry: EntryDetail;
}

export default function MainContent({ entry }: MainContentProps) {
  const layoutTree = useMemo(
    () => parseLayoutStructure(entry.layout_structure),
    [entry.layout_structure],
  );

  const renderDescriptions = (nodes: LayoutNode[], depth = 0) => {
    return nodes.map((node, index) => {
      const descId = node.entry_desc_id;
      if (!descId) return null;

      const desc = entry.descriptions.find((d) => d.id === Number(descId));
      if (!desc) return null;

      const slug = slugify(desc.title);
      // Determine header level based on depth (max h6)
      const headerLevel = Math.min(depth + 2, 6);
      const HeaderTag = `h${headerLevel}` as 'h2' | 'h3' | 'h4' | 'h5' | 'h6';

      return (
        <section
          key={node.ui_id || `desc-${desc.id}-${index}`}
          id={slug}
          className="mb-12 scroll-mt-8"
        >
          <HeaderTag
            className={`mb-4 border-b pb-2 font-semibold ${headerLevel === 2 ? 'text-2xl' : headerLevel === 3 ? 'text-xl' : 'text-lg'}`}
          >
            {desc.title}
          </HeaderTag>

          {desc.summary && <p className="mb-6 text-lg text-muted-foreground">{desc.summary}</p>}

          <ContentRenderer content={desc.article} />

          {/* Render children if they exist */}
          {node.children && node.children.length > 0 && (
            <div className="mt-8 ml-4 border-l border-border/30 pl-4">
              {renderDescriptions(node.children, depth + 1)}
            </div>
          )}
        </section>
      );
    });
  };

  return (
    <article className="prose prose-slate dark:prose-invert max-w-none">
      <h1 className="mb-8 text-4xl font-bold">{entry.name}</h1>

      {layoutTree && layoutTree.length > 0
        ? renderDescriptions(layoutTree)
        : entry.descriptions.map((desc) => {
            const slug = slugify(desc.title);
            return (
              <section key={`fallback-desc-${desc.id}`} id={slug} className="mb-12 scroll-mt-8">
                <h2 className="mb-4 border-b pb-2 text-2xl font-semibold">{desc.title}</h2>
                {desc.summary && (
                  <p className="mb-6 text-lg text-muted-foreground">{desc.summary}</p>
                )}
                <ContentRenderer content={desc.article} />
              </section>
            );
          })}

      {(!layoutTree || layoutTree.length === 0) &&
        (!entry.descriptions || entry.descriptions.length === 0) && (
          <div className="mt-8 border-t border-border py-12 text-center text-muted-foreground">
            <p>No content available for this entry.</p>
          </div>
        )}
    </article>
  );
}
