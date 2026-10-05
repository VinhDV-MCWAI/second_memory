'use client';

import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Download, Upload, FileSpreadsheet, FileText } from 'lucide-react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import toast from 'react-hot-toast';
import { useTranslations } from 'next-intl';
import type { ExportFormat, ImportFormat, ImportExportProps } from '@/shared/types/data-table.types';
import { EXPORT_FORMATS } from '@/shared/config/constant';

export type { ExportFormat, ImportFormat } from '@/shared/types/data-table.types';

export function ImportExport({
  onExport,
  onImport,
  onDownloadTemplate,
  exportFormats = [EXPORT_FORMATS.CSV, EXPORT_FORMATS.EXCEL],
  importFormats = [EXPORT_FORMATS.CSV, EXPORT_FORMATS.EXCEL],
  moduleName = 'data',
}: ImportExportProps) {
  const t = useTranslations();
  const [isOpen, setIsOpen] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);

  const handleExport = async (format: ExportFormat) => {
    if (!onExport) return;

    setIsProcessing(true);
    try {
      await onExport(format);
      toast.success(t('importExport.exported', { moduleName, format: format.toUpperCase() }));
      setIsOpen(false);
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    } catch (_error) {
      toast.error(t('importExport.exportFailed'));
    } finally {
      setIsProcessing(false);
    }
  };

  const handleImport = async (format: ImportFormat) => {
    if (!onImport || !selectedFile) {
      toast.error(t('importExport.selectFile'));
      return;
    }

    setIsProcessing(true);
    try {
      await onImport(selectedFile, format);
      toast.success(t('importExport.imported', { moduleName }));
      setSelectedFile(null);
      setIsOpen(false);
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    } catch (_error) {
      toast.error(t('importExport.importFailed'));
    } finally {
      setIsProcessing(false);
    }
  };

  const handleDownloadTemplate = async (format: ImportFormat) => {
    if (!onDownloadTemplate) return;

    setIsProcessing(true);
    try {
      await onDownloadTemplate(format);
      toast.success(t('importExport.templateDownloaded', { format: format.toUpperCase() }));
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    } catch (_error) {
      toast.error(t('importExport.templateDownloadFailed'));
    } finally {
      setIsProcessing(false);
    }
  };

  const getFormatIcon = (format: string) => {
    switch (format) {
      case EXPORT_FORMATS.CSV:
        return <FileText className="h-4 w-4" />;
      case EXPORT_FORMATS.EXCEL:
        return <FileSpreadsheet className="h-4 w-4" />;
      default:
        return <FileText className="h-4 w-4" />;
    }
  };

  return (
    <>
      <Button
        variant="outline"
        size="sm"
        onClick={() => setIsOpen(true)}
        className="gap-2"
      >
        <Download className="h-4 w-4" />
        {t('importExport.title')}
      </Button>

      <Dialog open={isOpen} onOpenChange={setIsOpen}>
        <DialogContent className="sm:max-w-[500px]">
          <DialogHeader>
            <DialogTitle>{t('importExport.title')} {moduleName}</DialogTitle>
            <DialogDescription>
              {t('importExport.description')}
            </DialogDescription>
          </DialogHeader>

          <Tabs defaultValue="export" className="w-full">
            <TabsList className="grid w-full grid-cols-2">
              <TabsTrigger value="export">{t('importExport.export')}</TabsTrigger>
              <TabsTrigger value="import">{t('importExport.import')}</TabsTrigger>
            </TabsList>

            {/* Export Tab */}
            <TabsContent value="export" className="space-y-4">
              <div className="space-y-2">
                <Label>{t('importExport.selectFormat')}</Label>
                <div className="grid grid-cols-2 gap-2">
                  {exportFormats.map((format) => (
                    <Button
                      key={format}
                      variant="outline"
                      onClick={() => handleExport(format)}
                      disabled={isProcessing}
                      className="gap-2"
                    >
                      {getFormatIcon(format)}
                      {format.toUpperCase()}
                    </Button>
                  ))}
                </div>
              </div>
              <p className="text-sm text-muted-foreground">
                {t('importExport.exportDescription')}
              </p>
            </TabsContent>

            {/* Import Tab */}
            <TabsContent value="import" className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="file">{t('importExport.selectFile')}</Label>
                <Input
                  id="file"
                  type="file"
                  accept={importFormats.map(f => f === EXPORT_FORMATS.CSV ? '.csv' : '.xlsx,.xls').join(',')}
                  onChange={(e) => setSelectedFile(e.target.files?.[0] || null)}
                  disabled={isProcessing}
                />
                {selectedFile && (
                  <p className="text-sm text-muted-foreground">
                    {t('importExport.selected')}: {selectedFile.name}
                  </p>
                )}
              </div>

              <div className="space-y-2">
                <Label>{t('importExport.importAs')}</Label>
                <div className="grid grid-cols-2 gap-2">
                  {importFormats.map((format) => (
                    <Button
                      key={format}
                      variant="outline"
                      onClick={() => handleImport(format)}
                      disabled={isProcessing || !selectedFile}
                      className="gap-2"
                    >
                      <Upload className="h-4 w-4" />
                      {format.toUpperCase()}
                    </Button>
                  ))}
                </div>
              </div>

              {onDownloadTemplate && (
                <>
                  <div className="relative">
                    <div className="absolute inset-0 flex items-center">
                      <span className="w-full border-t" />
                    </div>
                    <div className="relative flex justify-center text-xs uppercase">
                      <span className="bg-background px-2 text-muted-foreground">
                        {t('importExport.or')}
                      </span>
                    </div>
                  </div>

                  <div className="space-y-2">
                    <Label>{t('importExport.downloadTemplate')}</Label>
                    <div className="grid grid-cols-2 gap-2">
                      {importFormats.map((format) => (
                        <Button
                          key={format}
                          variant="secondary"
                          onClick={() => handleDownloadTemplate(format)}
                          disabled={isProcessing}
                          className="gap-2"
                        >
                          <Download className="h-4 w-4" />
                          {format.toUpperCase()}
                        </Button>
                      ))}
                    </div>
                  </div>
                </>
              )}
            </TabsContent>
          </Tabs>

          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setIsOpen(false)}
              disabled={isProcessing}
            >
              {t('common.close')}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
