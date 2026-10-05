'use client';

import { useState, useEffect, useCallback } from 'react';
import { useTranslations } from 'next-intl';
import { 
  Dialog, 
  DialogContent, 
  DialogHeader, 
  DialogTitle, 
  DialogDescription,
  DialogFooter 
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Folder, Image as ImageIcon, File as FileIcon, ChevronRight, ArrowUp, RefreshCw, Loader2 } from 'lucide-react';
import { mediaFileService } from '@/shared/services/modules/media-file.service';
import type { MediaFile } from '@/shared/types/media-file.types';
import Image from 'next/image';
import { cn } from '@/shared/utils/cn';

interface MediaSelectorModalProps {
  open: boolean;
  onClose: () => void;
  onSelect: (media: MediaFile) => void;
  allowedMimeTypes?: string[]; // e.g., ['image/']
  title?: string;
}

export function MediaSelectorModal({ 
  open, 
  onClose, 
  onSelect, 
  allowedMimeTypes = ['image/'],
  title 
}: MediaSelectorModalProps) {
  const t = useTranslations('common'); // fallback to common if specific not found
  const [currentPath, setCurrentPath] = useState<string>('/');
  const [files, setFiles] = useState<MediaFile[]>([]);
  const [loading, setLoading] = useState(false);
  const [selectedFile, setSelectedFile] = useState<MediaFile | null>(null);

  const fetchFiles = useCallback(async (path: string) => {
    try {
      setLoading(true);
      // Fetch both files and folders. We assume listing without is_file returns everything.
      // If backend logic differs, we might need two calls or adjust param.
      // Based on repo analysis: optional is_file => returns both.
      const response = await mediaFileService.list({ 
        parent_path: path,
        per_page: 1000, // Fetch all for now
        order_by: 'original_name',
        order_direction: 'asc'
      });
      
      // Separate folders and files for better UI organization if needed, 
      // or just list them. Let's sort: Folders first, then Files.
      
      // Safety check: Backend might return { data: [...] } or just [...] or something else.
      // Based on types, it should be MediaApiListResponse { data: MediaFile[] }.
      // But if response.data is undefined, we fall back to empty array or check if response itself is array.
      const listData = response?.data || (Array.isArray(response) ? response : []) || [];

      const sorted = listData.sort((a: any, b: any) => {
        if (a.is_file === b.is_file) {
          return a.original_name.localeCompare(b.original_name);
        }
        return a.is_file ? 1 : -1; // Folders (is_file=false) come first
      });

      setFiles(sorted);
    } catch (error) {
      console.error('Failed to fetch media files', error);
    } finally {
        setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (open) {
      fetchFiles(currentPath);
      setSelectedFile(null); // Reset selection on open
    }
  }, [open, currentPath, fetchFiles]);

  const handleFolderClick = (folder: MediaFile) => {
    // virtual_path usually includes the full path e.g. /uploads/folder1/
    // We can just use that.
    setCurrentPath(folder.virtual_path);
  };

  const handleUpLevel = () => {
    if (currentPath === '/') return;
    // Remove last segment
    // /a/b/ -> /a/
    // /a/ -> /
    const parts = currentPath.split('/').filter(p => p);
    parts.pop();
    const newPath = parts.length > 0 ? '/' + parts.join('/') + '/' : '/';
    setCurrentPath(newPath);
  };

  const isAllowed = (file: MediaFile) => {
    if (!file.is_file) return true; // Folders are always "allowed" to traverse
    if (!allowedMimeTypes || allowedMimeTypes.length === 0) return true;
    return allowedMimeTypes.some(type => {
      if (type.endsWith('/')) { 
        // Prefix match e.g. "image/"
        return file.mime_type?.startsWith(type.slice(0, -1));
      }
      return file.mime_type === type;
    });
  };

  const handleFileClick = (file: MediaFile) => {
    if (!isAllowed(file)) return;
    setSelectedFile(file);
  };

  const handleConfirm = () => {
    if (selectedFile) {
      onSelect(selectedFile);
      onClose();
    }
  };

  return (
    <Dialog open={open} onOpenChange={(val) => !val && onClose()}>
      <DialogContent className="max-w-4xl h-[80vh] flex flex-col p-0 gap-0">
        <DialogHeader className="p-6 pb-2">
          <DialogTitle>{title || 'Select Media'}</DialogTitle>
          <DialogDescription className="hidden">
            Browse and select media files from your library.
          </DialogDescription>
        </DialogHeader>

        {/* Toolbar / Breadcrumbs */}
        <div className="px-6 py-2 bg-secondary/20 flex items-center gap-2 border-y">
            <Button 
                variant="ghost" 
                size="icon" 
                onClick={handleUpLevel} 
                disabled={currentPath === '/'}
                title="Up one level"
            >
                <ArrowUp className="h-4 w-4" />
            </Button>
            
            <div className="flex-1 font-mono text-sm truncate flex items-center">
                <span className="text-muted-foreground mr-1">Path:</span>
                {currentPath}
            </div>

            <Button variant="ghost" size="icon" onClick={() => fetchFiles(currentPath)} title="Refresh">
                 <RefreshCw className={cn("h-4 w-4", loading && "animate-spin")} />
            </Button>
        </div>

        {/* Main Content Area */}
        <div className="flex-1 flex overflow-hidden">
            {/* File Grid */}
            <div className="flex-1 overflow-y-auto p-6">
                {loading && files.length === 0 ? (
                    <div className="flex justify-center items-center h-full">
                        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                    </div>
                ) : files.length === 0 ? (
                    <div className="flex flex-col justify-center items-center h-full text-muted-foreground">
                        <p>No files found in this folder</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        {files.map((file) => {
                            const allowed = isAllowed(file);
                            return (
                                <div 
                                    key={file.id}
                                    className={cn(
                                        "group border rounded-lg p-3 cursor-pointer transition-all hover:bg-accent/50 flex flex-col items-center gap-2 text-center relative",
                                        selectedFile?.id === file.id && "bg-accent ring-2 ring-primary",
                                        !allowed && "opacity-50 grayscale cursor-not-allowed"
                                    )}
                                    onClick={() => file.is_file ? handleFileClick(file) : handleFolderClick(file)}
                                    onDoubleClick={() => {
                                        if (file.is_file) {
                                            if (allowed) {
                                                setSelectedFile(file);
                                                // need to wait for state update or just pass file directly? 
                                                // onSelect(file); onClose(); 
                                                // Let's stick to single click select + confirm button for safety, or dblclick to confirm.
                                                onSelect(file);
                                                onClose();
                                            }
                                        } else {
                                            handleFolderClick(file);
                                        }
                                    }}
                                >
                                    <div className="h-20 w-full flex items-center justify-center relative overflow-hidden rounded bg-background/50">
                                        {!file.is_file ? (
                                            <Folder className="h-12 w-12 text-blue-400 fill-blue-400/20" />
                                        ) : (
                                            file.mime_type?.startsWith('image/') && file.url ? (
                                                <Image 
                                                    src={file.url} 
                                                    alt={file.original_name} 
                                                    fill 
                                                    className="object-contain" 
                                                    unoptimized 
                                                />
                                            ) : (
                                                <FileIcon className="h-10 w-10 text-gray-400" />
                                            )
                                        )}
                                    </div>
                                    <div className="w-full">
                                        <p className="text-xs truncate font-medium w-full" title={file.original_name}>
                                            {file.original_name}
                                        </p>
                                        <p className="text-[10px] text-muted-foreground">
                                            {file.is_file && file.size ? mediaFileService.formatFileSize(file.size) : 'Folder'}
                                        </p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>

        </div>

        <DialogFooter className="p-4 border-t bg-muted/10">
          <div className="flex justify-between w-full items-center">
            <span className="text-sm text-muted-foreground">
                {selectedFile ? '1 item selected' : 'No item selected'}
            </span>
            <div className="flex gap-2">
                <Button variant="outline" onClick={onClose}>
                    Cancel
                </Button>
                <Button onClick={handleConfirm} disabled={!selectedFile}>
                    Select
                </Button>
            </div>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
