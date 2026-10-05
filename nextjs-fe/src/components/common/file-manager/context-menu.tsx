import {
  ContextMenu,
  ContextMenuContent,
  ContextMenuItem,
  ContextMenuSeparator,
  ContextMenuShortcut,
  ContextMenuTrigger,
} from '@/components/ui/context-menu';
import {
  Eye,
  Pencil,
  Move,
  Copy,
  Trash,
  Download,
} from 'lucide-react';
import { useTranslations } from 'next-intl';
import type { FileContextMenuProps } from '@/shared/types/file-manager.types';
import { KEYBOARD_KEYS } from '@/shared/config/constant';

export const FileContextMenu = ({
  children,
  file,
  onPreview,
  onRename,
  onMove,
  onCopy,
  onDelete,
  onDownload,
}: FileContextMenuProps) => {
  const t = useTranslations('fileManager');

  return (
    <ContextMenu>
      <ContextMenuTrigger asChild>{children}</ContextMenuTrigger>
      <ContextMenuContent className="w-64">
        <ContextMenuItem onClick={() => onPreview(file)}>
          <Eye className="mr-2 h-4 w-4" />
          {t('preview')}
          <ContextMenuShortcut>{KEYBOARD_KEYS.ENTER}</ContextMenuShortcut>
        </ContextMenuItem>
        <ContextMenuItem onClick={() => onDownload?.(file)}>
          <Download className="mr-2 h-4 w-4" />
          {t('download')}
        </ContextMenuItem>
        <ContextMenuSeparator />
        <ContextMenuItem onClick={() => onRename(file)}>
          <Pencil className="mr-2 h-4 w-4" />
          {t('rename')}
          <ContextMenuShortcut>{KEYBOARD_KEYS.F2}</ContextMenuShortcut>
        </ContextMenuItem>
        <ContextMenuItem onClick={() => onMove(file)}>
          <Move className="mr-2 h-4 w-4" />
          {t('move')}
        </ContextMenuItem>
        <ContextMenuItem onClick={() => onCopy(file)}>
          <Copy className="mr-2 h-4 w-4" />
          {t('copy')}
        </ContextMenuItem>
        <ContextMenuSeparator />
        <ContextMenuItem
          onClick={() => onDelete(file)}
          className="text-red-600 focus:text-red-600"
        >
          <Trash className="mr-2 h-4 w-4" />
          {t('delete')}
          <ContextMenuShortcut>{KEYBOARD_KEYS.DELETE}</ContextMenuShortcut>
        </ContextMenuItem>
      </ContextMenuContent>
    </ContextMenu>
  );
};
