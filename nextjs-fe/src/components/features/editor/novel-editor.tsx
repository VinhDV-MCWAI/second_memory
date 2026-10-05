'use client';

import { useEffect, useState } from 'react';
import { EditorContent, useEditor, type JSONContent, type Editor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import { TextStyle } from '@tiptap/extension-text-style';
import { Color } from '@tiptap/extension-color';
import Highlight from '@tiptap/extension-highlight';
import { Video } from './extensions/video';
import { EditorBubbleMenu } from './editor-bubble-menu';
import { EditorToolbar } from './editor-toolbar';
import { MediaSelectorModal } from '@/components/common/media-selector-modal';
import { cn } from '@/shared/utils/cn';
import type { MediaFile } from '@/shared/types/media-file.types';

interface NovelEditorProps {
  content?: string | JSONContent | null;
  onChange?: (content: JSONContent) => void;
  placeholder?: string;
  editable?: boolean;
  className?: string;
}

export function NovelEditor({
  content,
  onChange,
  placeholder = 'Start writing...',
  editable = true,
  className,
}: NovelEditorProps) {
  const [hydrated, setHydrated] = useState(false);
  const [showMediaModal, setShowMediaModal] = useState(false);

  const editor = useEditor({
    immediatelyRender: false,
    extensions: [
      StarterKit.configure({
        heading: {
          levels: [1, 2, 3, 4, 5, 6],
        },
      }),
      Placeholder.configure({
        placeholder,
      }),
      Image.configure({
        HTMLAttributes: {
          class: 'rounded-lg max-w-full h-auto',
        },
      }),
      Link.configure({
        openOnClick: false,
        HTMLAttributes: {
          class: 'text-blue-500 underline cursor-pointer',
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
    editable,
    onUpdate: ({ editor }: { editor: Editor }) => {
      if (onChange) {
        onChange(editor.getJSON());
      }
    },
    editorProps: {
      attributes: {
        class: cn(
          'prose prose-sm sm:prose-base lg:prose-lg xl:prose-xl',
          'dark:prose-invert',
          'max-w-none',
          'focus:outline-none',
          'min-h-[200px] p-4',
          'dark:bg-gray-900 dark:text-gray-100',
          className
        ),
      },
    },
  }, []);

  useEffect(() => {
    // Mark as hydrated after mount
    const timer = setTimeout(() => setHydrated(true), 0);
    return () => clearTimeout(timer);
  }, []);

  useEffect(() => {
    if (editor && content !== undefined) {
      const currentContent = editor.getJSON();
      const newContent = typeof content === 'string' ? 
        (content ? JSON.parse(content) : { type: 'doc', content: [] }) : 
        content;
      
      // Only update if content actually changed to avoid cursor issues
      if (JSON.stringify(currentContent) !== JSON.stringify(newContent)) {
        editor.commands.setContent(newContent || { type: 'doc', content: [] });
      }
    }
  }, [content, editor]);

  useEffect(() => {
    if (editor) {
      editor.setEditable(editable);
    }
  }, [editable, editor]);

  const handleMediaSelect = (media: MediaFile) => {
    if (editor && media.url) {
      if (media.mime_type?.startsWith('video/')) {
        editor.chain().focus().setVideo({ src: media.url }).run();
      } else {
        editor.chain().focus().setImage({ src: media.url }).run();
      }
    }
    setShowMediaModal(false);
  };

  if (!hydrated) {
    return (
      <div className={cn('border rounded-lg p-4 min-h-[200px] bg-gray-50 dark:bg-gray-900 dark:border-gray-700', className)}>
        <div className="animate-pulse space-y-3">
          <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
          <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
        </div>
      </div>
    );
  }

  if (!editor) {
    return null;
  }

  return (
    <>
      <div className={cn('border rounded-lg overflow-hidden dark:border-gray-700 dark:bg-gray-900', className)}>
        {editable && (
          <EditorToolbar editor={editor} onImageUpload={() => setShowMediaModal(true)} />
        )}
        <EditorBubbleMenu />
        <EditorContent editor={editor} />
      </div>
      
      <MediaSelectorModal
        open={showMediaModal}
        onClose={() => setShowMediaModal(false)}
        onSelect={handleMediaSelect}
        allowedMimeTypes={['image/', 'video/']}
        title="Select Media"
      />
    </>
  );
}
