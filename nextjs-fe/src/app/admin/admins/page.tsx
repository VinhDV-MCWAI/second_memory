'use client';

import { useState } from 'react';
import { useApiData } from '@/shared/hooks/useApiData';
import { useCrud } from '@/shared/hooks/useCrud';
import { useActionLock } from '@/shared/hooks/useActionLock';
import { apiClient } from '@/shared/api/client';
import { notification } from '@/shared/utils';
import { AdminLayout } from '@/components/layout/admin-layout';
import { PageHeader } from '@/components/layout/page-header';
import { DataTable, type Column } from '@/components/common/data-table/data-table';
import { Pagination } from '@/components/common/data-table/pagination';
import { FilterPanel, type FilterField } from '@/components/common/data-table/filter-panel';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import type { AdminMst } from '@/shared/types/api';
import { API_ENDPOINTS } from '@/shared/api';
import { AdminStatus, AdminStatusLabels } from '@/shared/enums';
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
import { AdminForm } from '@/components/forms/admin-form';
import { useTranslations } from 'next-intl';

export default function AdminListPage() {
  const [page, setPage] = useState<number>(PAGINATION.DEFAULT_PAGE);
  const [perPage, setPerPage] = useState<number>(PAGINATION.DEFAULT_PER_PAGE);
  const [filters, setFilters] = useState({});
  const [sortBy, setSortBy] = useState<string>(SORT_FIELDS.CREATED_AT);
  const [sortOrder, setSortOrder] = useState<SortOrder>(SORT_ORDER.DESC);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  
  // Dialog states
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [deleteIds, setDeleteIds] = useState<number[]>([]);
  const [formDialogOpen, setFormDialogOpen] = useState(false);
  const [editingAdmin, setEditingAdmin] = useState<AdminMst | null>(null);

  const tCommon = useTranslations('common');
  const tEntities = useTranslations('entities');
  const tFields = useTranslations('fields');
  const tManagement = useTranslations('management');
  const tCrud = useTranslations('crud');
  const tBulkActions = useTranslations('bulkActions');

  const { data, loading, pagination, refetch } = useApiData<AdminMst>(
    API_ENDPOINTS.MASTER.ADMIN,
    { page, per_page: perPage, filters, sort_by: sortBy, sort_order: sortOrder }
  );

  const { remove } = useCrud<AdminMst>(API_ENDPOINTS.MASTER.ADMIN);

  const handleCreate = () => {
    setEditingAdmin(null);
    setFormDialogOpen(true);
  };

  const handleEdit = (admin: AdminMst) => {
    setEditingAdmin(admin);
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

  const handleBulkStatusChange = async (ids: number[], isActive: boolean) => {
    try {
      await Promise.all(
        ids.map((id) =>
          apiClient.put(`${API_ENDPOINTS.MASTER.ADMIN}/update/${id}`, {
            id,
            is_active: isActive,
          })
        )
      );
      notification.success(
        tCommon('updatedSuccessfully')
      );
      refetch();
    } catch {
       notification.error(tCommon('somethingWentWrong'));
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

  const columns: Column<AdminMst>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'first_name', label: tFields('firstName'), sortable: true },
    { key: 'last_name', label: tFields('lastName'), sortable: true },
    { key: 'email', label: tFields('email'), sortable: true },
    {
      key: 'status',
      label: tFields('status'),
      sortable: true,
      render: (item) => (
        <Badge variant={item.is_active ? 'default' : 'secondary'}>
          {item.is_active ? tCommon('active') : tCommon('inactive')}
        </Badge>
      ),
    },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    { key: 'first_name', label: tFields('firstName'), type: 'text' },
    { key: 'email', label: tFields('email'), type: 'text' },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: [
        { value: AdminStatus.ACTIVE, label: AdminStatusLabels[AdminStatus.ACTIVE] },
        { value: AdminStatus.INACTIVE, label: AdminStatusLabels[AdminStatus.INACTIVE] },
      ],
    },
  ];

  const searchFields: SearchField[] = [
    { key: 'first_name', label: tFields('firstName'), type: 'text' },
    { key: 'email', label: tFields('email'), type: 'text' },
    { 
      key: 'status', 
      label: tFields('status'), 
      type: 'select', 
      options: [
        { value: AdminStatus.ACTIVE.toString(), label: AdminStatusLabels[AdminStatus.ACTIVE] },
        { value: AdminStatus.INACTIVE.toString(), label: AdminStatusLabels[AdminStatus.INACTIVE] },
      ]
    },
    { key: SORT_FIELDS.CREATED_AT, label: tFields('createdAt'), type: 'date' },
  ];

  const bulkActions: BulkAction[] = [
    {
      label: tBulkActions('deleteSelected'),
      icon: <Trash2 className="h-4 w-4" />,
      variant: 'destructive',
      onClick: async (_ids) => { await remove(_ids); },
      confirmMessage: tCrud('deleteConfirm', { count: selectedIds.length, entity: tEntities('admin').toLowerCase() }),
      confirmTitle: tCrud('deleteEntity', { entity: tEntities('admins') }),
    },
    { 
      label: tBulkActions('activateSelected'), 
      icon: <CheckCircle className="h-4 w-4" />, 
      onClick: async (ids) => handleBulkStatusChange(ids, true),
      confirmMessage: tBulkActions('activateConfirm', { count: selectedIds.length }),
    },
    { 
      label: tBulkActions('deactivateSelected'), 
      icon: <XCircle className="h-4 w-4" />, 
      onClick: async (ids) => handleBulkStatusChange(ids, false),
      confirmMessage: tBulkActions('deactivateConfirm', { count: selectedIds.length }),
    },
  ];

  const handleAdvancedSearch = (criteria: SearchCriteria[]) => {
    const newFilters = criteria.reduce(
      (acc, c) => ({ ...acc, [c.field]: c.value }), 
      {}
    );
    setFilters(newFilters);
    setPage(PAGINATION.DEFAULT_PAGE);
  };

  // TODO: Implement import logic
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const handleImport = async (_file: File, _format: string) => {
    refetch();
  };

  // TODO: Implement export logic
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const handleExport = async (_format: string) => {
    // TODO: Implement export logic
  };

  return (
    <AdminLayout>
      <PageHeader
        title={tManagement('title', { entity: tEntities('admins') })}
        description={tManagement('description', { entity: tEntities('admins').toLowerCase() })}
        breadcrumbs={[
          { label: tCommon('admin'), href: ADMIN_ROUTES.DASHBOARD },
          { label: tEntities('admins'), isActive: true },
        ]}
        action={
          <Button onClick={handleCreate}>
            <Plus className="mr-2 h-4 w-4" /> {tCrud('createEntity', { entity: tEntities('admin') })}
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
            storageKey="admin-filters" 
          />
          <ImportExport onExport={handleExport} onImport={handleImport} moduleName={tEntities('admins')} />
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
            const admin = data.find((a: AdminMst) => a.id === id);
            if (admin) handleEdit(admin);
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

      {/* Create/Edit Admin Modal */}
      {/* Create/Edit Admin Modal */}
      <Dialog open={formDialogOpen} onOpenChange={setFormDialogOpen}>
        <DialogContent className="max-w-2xl max-h-[90vh] p-0 flex flex-col gap-0 overflow-hidden">
          {/* Header - Fixed */}
          <div className="shrink-0 px-6 pt-6 pb-4 border-b bg-background">
            <DialogHeader>
              <DialogTitle>{editingAdmin ? tCrud('editEntity', { entity: tEntities('admin') }) : tCrud('createEntity', { entity: tEntities('admin') })}</DialogTitle>
              <DialogDescription>
                {editingAdmin ? tCrud('editDescription', { entity: tEntities('admin').toLowerCase() }) : tCrud('createDescription', { entity: tEntities('admin').toLowerCase() })}
              </DialogDescription>
            </DialogHeader>
          </div>
          
          {/* Body - Scrollable */}
          <div className="flex-1 overflow-y-auto min-h-0">
            <div className="px-6 py-4">
              <AdminForm
                initialData={editingAdmin}
                onSuccess={handleFormSuccess}
                onCancel={() => setFormDialogOpen(false)}
              />
            </div>
          </div>
          
          {/* Footer - Fixed */}
          <div className="shrink-0 px-6 py-4 border-t bg-muted/20">
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setFormDialogOpen(false)}>
                {tCommon('cancel')}
              </Button>
              <Button type="button" onClick={() => setFormDialogOpen(false)}>
                {tCommon('done')}
              </Button>
            </DialogFooter>
          </div>
        </DialogContent>
      </Dialog>

      <ConfirmDialog
        open={deleteDialogOpen}
        onOpenChange={setDeleteDialogOpen}
        title={tCrud('deleteEntity', { entity: tEntities('admin') + '(s)' })}
        description={tCrud('deleteConfirm', { count: deleteIds.length, entity: tEntities('admin').toLowerCase() })}
        onConfirm={confirmDelete}
        confirmText={tCommon('delete')}

        variant="destructive"
        isLoading={isDeleteProcessing}
      />
    </AdminLayout>
  );
}
