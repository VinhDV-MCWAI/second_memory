'use client';

import { useState } from 'react';
import { useApiData } from '@/shared/hooks/useApiData';
import { useCrud } from '@/shared/hooks/useCrud';
import { useActionLock } from '@/shared/hooks/useActionLock';
import { AdminLayout } from '@/components/layout/admin-layout';
import { PageHeader } from '@/components/layout/page-header';
import { DataTable, type Column } from '@/components/common/data-table/data-table';
import { Pagination } from '@/components/common/data-table/pagination';
import { FilterPanel, type FilterField } from '@/components/common/data-table/filter-panel';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { Button } from '@/components/ui/button';
import type { EntryDescriptionMgmt } from '@/shared/types/api';
import { StatusEnum, StatusEnumLabels } from '@/shared/enums';
import { API_ENDPOINTS } from '@/shared/api';
import { 
  SORT_ORDER, 
  SORT_FIELDS,
  type SortOrder, 
  PAGINATION, 
  ADMIN_ROUTES,
  UI_CONSTANTS 
} from '@/shared/config';
import { AdvancedSearch } from '@/components/common/advanced-search';
import type { SearchField, SearchCriteria } from '@/shared/types/data-table.types';
import { SavedFilters } from '@/components/common/saved-filters';
import { BulkActions, type BulkAction } from '@/components/common/bulk-actions';
import { ImportExport } from '@/components/common/import-export';
import { Trash2, CheckCircle, XCircle, Plus } from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { EntryDescriptionForm } from '@/components/forms/entry-description-form';
import { useTranslations } from 'next-intl';

export default function EntryDescriptionListPage() {
  const [page, setPage] = useState<number>(PAGINATION.DEFAULT_PAGE);
  const [perPage, setPerPage] = useState<number>(PAGINATION.DEFAULT_PER_PAGE);
  const [filters, setFilters] = useState({});
  const [sortBy, setSortBy] = useState<string>(SORT_FIELDS.ORDER);
  const [sortOrder, setSortOrder] = useState<SortOrder>(SORT_ORDER.ASC);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);

  // Dialog states
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [deleteIds, setDeleteIds] = useState<number[]>([]);
  const [formDialogOpen, setFormDialogOpen] = useState(false);
  const [editingDescription, setEditingDescription] = useState<EntryDescriptionMgmt | null>(null);

  const tCommon = useTranslations('common');
  const tEntities = useTranslations('entities');
  const tFields = useTranslations('fields');
  const tManagement = useTranslations('management');
  const tCrud = useTranslations('crud');
  const tBulkActions = useTranslations('bulkActions');

  const { data, loading, pagination, refetch } = useApiData<EntryDescriptionMgmt>(
    API_ENDPOINTS.MANAGEMENT.ENTRY_DESCRIPTION,
    { page, per_page: perPage, filters, sort_by: sortBy, sort_order: sortOrder }
  );

  const { remove } = useCrud<EntryDescriptionMgmt>(API_ENDPOINTS.MANAGEMENT.ENTRY_DESCRIPTION);

  const handleCreate = () => {
    setEditingDescription(null);
    setFormDialogOpen(true);
  };

  const handleEdit = (description: EntryDescriptionMgmt) => {
    setEditingDescription(description);
    setFormDialogOpen(true);
  };

  const handleFormSuccess = () => {
    setFormDialogOpen(false);
  };

  const handleDelete = async (ids: number[]) => {
    setDeleteIds(ids);
    setDeleteDialogOpen(true);
  };

  const { execute, isLoading: isDeleteProcessing } = useActionLock({ delay: UI_CONSTANTS.ACTION_DELAY_MS });

  const confirmDelete = async () => {
    try {
      await execute(async () => {
        await remove(deleteIds);
        setSelectedIds([]);
        setDeleteIds([]);
        setDeleteDialogOpen(false);
      });
    } catch {
      // Global Error Handler will pick it up
    }
  };

  const handleSort = (column: string) => {
    if (sortBy === column) {
      setSortOrder(sortOrder === SORT_ORDER.ASC ? SORT_ORDER.DESC : SORT_ORDER.ASC);
    } else {
      setSortBy(column);
      setSortOrder(SORT_ORDER.ASC);
    }
  };

  const columns: Column<EntryDescriptionMgmt>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    {
      key: 'status',
      label: tFields('status'),
      sortable: true,
      render: (item) => (
        <span className={`px-2 py-1 rounded-full text-xs ${
          item.status === StatusEnum.PUBLISHED ? 'bg-green-100 text-green-800' : 
          item.status === StatusEnum.ARCHIVED ? 'bg-gray-100 text-gray-800' : 
          'bg-yellow-100 text-yellow-800'
        }`}>
          {StatusEnumLabels[item.status as StatusEnum] || tCommon('unknown')}
        </span>
      ),
    },
    { 
      key: 'title', 
      label: tFields('title'), 
      sortable: true 
    },
    { 
      key: 'summary', 
      label: tFields('summary'),
      render: (item) => (
        <div className="max-w-md truncate" title={item.summary || ''}>
          {item.summary}
        </div>
      ),
    },
    { key: 'rank_order', label: tFields('order'), sortable: true },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    {
      key: 'entry_mgmt_id',
      label: tFields('entryId'),
      type: 'text',
      placeholder: tCommon('search'),
    },
  ];
  const searchFields: SearchField[] = [
    { key: 'entry_mgmt_id', label: tFields('entryId'), type: 'text' },
    { key: 'title', label: tFields('title'), type: 'text' },
    { key: 'summary', label: tFields('summary'), type: 'text' },
    { key: 'created_at', label: tFields('createdAt'), type: 'date' }
  ];
  const bulkActions: BulkAction[] = [
    { 
      label: tBulkActions('deleteSelected'), 
      icon: <Trash2 className="h-4 w-4" />, 
      variant: 'destructive', 
      onClick: async (ids) => { await remove(ids); }, 
      confirmMessage: tCrud('deleteConfirm', { count: selectedIds.length, entity: tEntities('entryDescription').toLowerCase() }), 
      confirmTitle: tCrud('deleteEntity', { entity: tEntities('entryDescriptions') }) 
    }, 
    { 
      label: tBulkActions('activateSelected'), 
      icon: <CheckCircle className="h-4 w-4" />, 
      onClick: async () => { refetch(); } 
    }, 
    { 
      label: tBulkActions('deactivateSelected'), 
      icon: <XCircle className="h-4 w-4" />, 
      onClick: async () => { refetch(); } 
    }
  ];

  const handleAdvancedSearch = (criteria: SearchCriteria[]) => {
    const newFilters = criteria.reduce((acc, c) => ({ ...acc, [c.field]: c.value }), {});
    setFilters(newFilters);
    setPage(PAGINATION.DEFAULT_PAGE);
  };

  const handleImport = async () => {
    refetch();
  };

  return (
    <AdminLayout>
      <PageHeader
        title={tManagement('title', { entity: tEntities('entryDescriptions') })}
        description={tManagement('description', { entity: tEntities('entryDescriptions').toLowerCase() })}
        breadcrumbs={[
          { label: tCommon('admin'), href: ADMIN_ROUTES.DASHBOARD },
          { label: tEntities('entryDescriptions'), isActive: true },
        ]}
        action={
          <Button onClick={handleCreate}>
            <Plus className="mr-2 h-4 w-4" /> {tCrud('createEntity', { entity: tEntities('entryDescription') })}
          </Button>
        }
      />

      <div className="mt-6 space-y-4">
        <div className="flex gap-2">
          <AdvancedSearch fields={searchFields} onSearch={handleAdvancedSearch} />
          <SavedFilters 
            currentFilters={filters} 
            onApplyFilter={(f) => { 
              setFilters(f); 
              setPage(PAGINATION.DEFAULT_PAGE); 
            }} 
            storageKey="entry-description-filters" 
          />
          <ImportExport onImport={handleImport} />
        </div>

        <FilterPanel
          filters={filters}
          onFilterChange={(newFilters) => {
            setFilters(newFilters);
            setPage(PAGINATION.DEFAULT_PAGE);
          }}
          onReset={() => {
            setFilters({});
            setPage(PAGINATION.DEFAULT_PAGE);
          }}
          fields={filterFields}
        />

        <BulkActions selectedIds={selectedIds} onClearSelection={() => setSelectedIds([])} actions={bulkActions} isLoading={loading} />

        <DataTable data={data}
          columns={columns}
          loading={loading}
          selectedIds={selectedIds}
          onSelectionChange={setSelectedIds}
          onSort={handleSort}
          sortBy={sortBy}
          sortOrder={sortOrder}
          onEdit={(id) => {
            const desc = data.find(d => d.id === id);
            if (desc) handleEdit(desc);
          }}
          onDelete={(id) => handleDelete([id])}
        />

        <Pagination
          pagination={pagination}
          page={page}
          onPageChange={setPage}
          perPage={perPage}
          onPerPageChange={(newPerPage) => {
            setPerPage(newPerPage);
            setPage(PAGINATION.DEFAULT_PAGE);
          }}
        />
      </div>

      {/* Create/Edit Entry Description Modal */}
      <Dialog open={formDialogOpen} onOpenChange={setFormDialogOpen}>
        <DialogContent className="max-w-4xl max-h-[90vh] p-0 flex flex-col gap-0 overflow-hidden">
          {/* Header - Fixed */}
          <div className="shrink-0 px-6 pt-6 pb-4 border-b bg-background">
            <DialogHeader>
              <DialogTitle>{editingDescription ? tCrud('editEntity', { entity: tEntities('entryDescription') }) : tCrud('createEntity', { entity: tEntities('entryDescription') })}</DialogTitle>
              <DialogDescription>
                {editingDescription ? tCrud('editDescription', { entity: tEntities('entryDescription').toLowerCase() }) : tCrud('createDescription', { entity: tEntities('entryDescription').toLowerCase() })}
              </DialogDescription>
            </DialogHeader>
          </div>
          
          {/* Body - Scrollable */}
          <div className="flex-1 overflow-y-auto min-h-0">
            <div className="px-6 py-4">
              <EntryDescriptionForm
                initialData={editingDescription}
                onSuccess={handleFormSuccess}
                onCancel={() => setFormDialogOpen(false)}
                hideActions={true}
              />
            </div>
          </div>
          
          {/* Footer - Fixed */}
          <div className="shrink-0 px-6 py-4 border-t bg-muted/20">
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setFormDialogOpen(false)}>
                {tCommon('cancel')}
              </Button>
              <Button type="submit" form="entryDescriptionForm">
                {editingDescription ? tCommon('update') : tCommon('create')}
              </Button>
            </DialogFooter>
          </div>
        </DialogContent>
      </Dialog>

      <ConfirmDialog
        open={deleteDialogOpen}
        onOpenChange={setDeleteDialogOpen}
        title={tCrud('deleteEntity', { entity: tEntities('entryDescriptions') })}
        description={tCrud('deleteConfirm', { count: deleteIds.length, entity: tEntities('entryDescription').toLowerCase() })}
        onConfirm={confirmDelete}
        confirmText={tCommon('delete')}

        variant="destructive"
        isLoading={isDeleteProcessing}
      />
    </AdminLayout>
  );
}
