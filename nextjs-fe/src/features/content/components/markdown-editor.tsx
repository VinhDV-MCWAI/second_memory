'use client';

import { useRef, useState } from 'react';
import { useTranslations } from 'next-intl';
import { FileText, Paperclip } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { ArticlePickerDialog } from '@/features/content/components/article-picker-dialog';
import { useMarkdownPreview } from '@/features/content/hooks/use-markdown-preview';
import { FilePickerDialog } from '@/features/media/components/file-picker-dialog';
import { usePermissions } from '@/shared/hooks/use-permissions';
import type { MediaItem } from '@/shared/types/models';

/** Files a single "Insert file" adds at most. */
const INSERT_FILES_MAX = 10;

type Tab = 'write' | 'preview';

interface MarkdownEditorProps {
  id: string;
  value: string;
  onChange: (value: string) => void;
  rows?: number;
  invalid?: boolean;
  /** The article being edited, so the picker does not offer it. */
  articleId?: number;
}

/**
 * Markdown with a Preview tab that shows exactly what the public page will (rendered by the API,
 * embeds included), and buttons that write the wiki syntax of ADR-0022 at the cursor:
 * `![[file:{id}|name]]` for files of the file manager, `[[slug]]` / `![[slug]]` for articles.
 */
export function MarkdownEditor({
  id,
  value,
  onChange,
  rows = 14,
  invalid,
  articleId,
}: MarkdownEditorProps) {
  const t = useTranslations('content.editor');
  const { can } = usePermissions();
  const preview = useMarkdownPreview();
  // Where the writer last put the cursor; before that, inserts go at the end
  const cursor = useRef<{ start: number; end: number } | null>(null);
  const [tab, setTab] = useState<Tab>('write');
  const [pickingFiles, setPickingFiles] = useState(false);
  const [pickingArticle, setPickingArticle] = useState(false);

  /** Inserts at the cursor; block syntax goes on lines of its own. */
  const insert = (text: string, block: boolean) => {
    const { start, end } = cursor.current ?? { start: value.length, end: value.length };
    const before = value.slice(0, start);
    const after = value.slice(end);
    const lead =
      block && before !== '' && !before.endsWith('\n\n')
        ? before.endsWith('\n')
          ? '\n'
          : '\n\n'
        : '';
    const inserted = `${lead}${text}${block ? '\n\n' : ''}`;
    cursor.current = { start: start + inserted.length, end: start + inserted.length };
    onChange(`${before}${inserted}${after}`);
  };

  const showTab = (next: string) => {
    setTab(next as Tab);
    if (next === 'preview') {
      preview.mutate(value);
    }
  };

  return (
    <Tabs value={tab} onValueChange={showTab}>
      <div className="flex flex-wrap items-center justify-between gap-2">
        <TabsList>
          <TabsTrigger value="write">{t('write')}</TabsTrigger>
          <TabsTrigger value="preview">{t('preview')}</TabsTrigger>
        </TabsList>
        {tab === 'write' && (
          <span className="flex gap-2">
            {can('media.list') && (
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setPickingFiles(true)}
              >
                <Paperclip className="mr-2 h-4 w-4" />
                {t('insertFile')}
              </Button>
            )}
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => setPickingArticle(true)}
            >
              <FileText className="mr-2 h-4 w-4" />
              {t('insertArticle')}
            </Button>
          </span>
        )}
      </div>
      <TabsContent value="write">
        <Textarea
          id={id}
          rows={rows}
          className="font-mono text-sm"
          value={value}
          onChange={(event) => onChange(event.target.value)}
          onSelect={(event) => {
            cursor.current = {
              start: event.currentTarget.selectionStart,
              end: event.currentTarget.selectionEnd,
            };
          }}
          aria-invalid={invalid}
        />
        <p className="mt-1 text-xs text-muted-foreground">{t('hint')}</p>
      </TabsContent>
      <TabsContent value="preview">
        <div className="min-h-40 rounded-md border p-4">
          {preview.isPending && <p className="text-sm text-muted-foreground">{t('rendering')}</p>}
          {preview.isError && <p className="text-sm text-destructive">{t('previewFailed')}</p>}
          {preview.data && (
            // Built by the API from Markdown: raw HTML is escaped and unsafe links removed (ADR-0021)
            <div
              className="markdown-body"
              dangerouslySetInnerHTML={{ __html: preview.data.html }}
            />
          )}
        </div>
      </TabsContent>

      {/* Mounted only while open: they list as soon as they render */}
      {pickingFiles && (
        <FilePickerDialog
          open
          onOpenChange={setPickingFiles}
          chosenIds={[]}
          limit={INSERT_FILES_MAX}
          onPick={(files: MediaItem[]) =>
            insert(files.map((file) => `![[file:${file.id}|${file.name}]]`).join('\n\n'), true)
          }
        />
      )}
      {pickingArticle && (
        <ArticlePickerDialog
          open
          onOpenChange={setPickingArticle}
          excludeId={articleId}
          onPick={(article, as) => {
            setPickingArticle(false);
            insert(as === 'embed' ? `![[${article.slug}]]` : `[[${article.slug}]]`, as === 'embed');
          }}
        />
      )}
    </Tabs>
  );
}
