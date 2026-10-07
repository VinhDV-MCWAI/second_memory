'use client';

import { useEffect } from 'react';
import { EditorContent, useEditor, type JSONContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import { TextStyle } from '@tiptap/extension-text-style';
import { Color } from '@tiptap/extension-color';
import Highlight from '@tiptap/extension-highlight';
import { Video } from './extensions/video';
import { cn } from '@/lib/utils';
import { useIsClient } from '@/hooks/use-is-client';

interface ContentRendererProps {
  content: string | JSONContent | null;
  className?: string;
}

export function ContentRenderer({ content, className }: ContentRendererProps) {
  const hydrated = useIsClient();

  const editor = useEditor({
    immediatelyRender: false,
    extensions: [
      StarterKit.configure({
        heading: {
          levels: [1, 2, 3, 4, 5, 6],
        },
      }),
      Image.configure({
        HTMLAttributes: {
          class: 'rounded-lg max-w-full h-auto',
        },
      }),
      Link.configure({
        openOnClick: true,
        HTMLAttributes: {
          class: 'text-blue-500 underline cursor-pointer hover:text-blue-700',
        },
      }),
      TextStyle,
      Color,
      Highlight.configure({
        multicolor: true,
      }),
      Video,
    ],
    content: content ? (typeof content === 'string' ? JSON.parse(content) : content) : undefined,
    editable: false,
    editorProps: {
      attributes: {
        class: cn(
          'prose prose-sm sm:prose-base lg:prose-lg xl:prose-xl',
          'max-w-none',
          'focus:outline-none',
          className,
        ),
      },
    },
  });

  useEffect(() => {
    if (editor && content !== undefined) {
      const newContent =
        typeof content === 'string'
          ? content
            ? JSON.parse(content)
            : { type: 'doc', content: [] }
          : content;

      editor.commands.setContent(newContent || { type: 'doc', content: [] });
    }
  }, [content, editor]);

  if (!hydrated) {
    return (
      <div className={cn('animate-pulse space-y-3', className)}>
        <div className="h-4 w-3/4 rounded bg-gray-200"></div>
        <div className="h-4 w-full rounded bg-gray-200"></div>
        <div className="h-4 w-5/6 rounded bg-gray-200"></div>
      </div>
    );
  }

  if (!editor) {
    return null;
  }

  return <EditorContent editor={editor} />;
}
