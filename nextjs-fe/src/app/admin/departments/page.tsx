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
import { Badge } from '@/components/ui/badge';
import { AdvancedSearch } from '@/components/common/advanced-search';
import type { SearchField, SearchCriteria } from '@/shared/types/data-table.types';
import { SavedFilters } from '@/components/common/saved-filters';
import { BulkActions, type BulkAction } from '@/components/common/bulk-actions';
import { ImportExport } from '@/components/common/import-export';
import { Trash2, CheckCircle, XCircle, Plus } from 'lucide-react';
import type { DepartmentMst } from '@/shared/types/api';
import { API_ENDPOINTS } from '@/shared/api';
import { 
  SORT_ORDER, 
  SORT_FIELDS, 
  type SortOrder, 
  PAGINATION, 
  ADMIN_ROUTES,
  UI_CONSTANTS 
} from '@/shared/config';
import { DepartmentStatus, DepartmentStatusLabels } from '@/shared/enums/enums';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { DepartmentForm } from '@/components/forms/department-form';
import { useTranslations } from 'next-intl';

export default function DepartmentListPage() {
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
  const [editingDepartment, setEditingDepartment] = useState<DepartmentMst | null>(null);

  const tCommon = useTranslations('common');
  const tEntities = useTranslations('entities');
  const tFields = useTranslations('fields');
  const tManagement = useTranslations('management');
  const tCrud = useTranslations('crud');
  const tBulkActions = useTranslations('bulkActions');

  const { data, loading, pagination, refetch } = useApiData<DepartmentMst>(
    API_ENDPOINTS.MASTER.DEPARTMENT,
    { page, per_page: perPage, filters, sort_by: sortBy, sort_order: sortOrder }
  );

  const { remove } = useCrud<DepartmentMst>(API_ENDPOINTS.MASTER.DEPARTMENT);

  const handleCreate = () => {
    setEditingDepartment(null);
    setFormDialogOpen(true);
  };

  const handleEdit = (department: DepartmentMst) => {
    setEditingDepartment(department);
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





  const columns: Column<DepartmentMst>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'code', label: tFields('code'), sortable: true },
    { key: 'name', label: tFields('name'), sortable: true },
    {
      key: 'status',
      label: tFields('status'),
      sortable: true,
      render: (dept) => {
        return (
          <Badge variant={dept.status === DepartmentStatus.ACTIVE ? 'default' : 'secondary'}>
            {DepartmentStatusLabels[dept.status as DepartmentStatus] || dept.status}
          </Badge>
        );
      },
    },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    { key: 'name', label: tFields('name'), type: 'text', placeholder: tCommon('search') },
    { key: 'code', label: tFields('code'), type: 'text', placeholder: tCommon('search') },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: [
        { value: DepartmentStatus.INACTIVE.toString(), label: DepartmentStatusLabels[DepartmentStatus.INACTIVE] },
        { value: DepartmentStatus.ACTIVE.toString(), label: DepartmentStatusLabels[DepartmentStatus.ACTIVE] },
        { value: DepartmentStatus.DRAFT.toString(), label: DepartmentStatusLabels[DepartmentStatus.DRAFT] },
        { value: DepartmentStatus.ARCHIVED.toString(), label: DepartmentStatusLabels[DepartmentStatus.ARCHIVED] },
      ],
    },
  ];

  const searchFields: SearchField[] = [
    { key: 'name', label: tFields('name'), type: 'text' },
    { key: 'code', label: tFields('code'), type: 'text' },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: Object.entries(DepartmentStatusLabels).map(([value, label]) => ({
        value: value.toString(),
        label
      }))
    },
    { key: 'created_at', label: tFields('createdAt'), type: 'date' },
  ];

  const bulkActions: BulkAction[] = [
    { 
      label: tBulkActions('deleteSelected'), 
      icon: <Trash2 className="h-4 w-4" />, 
      variant: 'destructive', 
      onClick: async (ids) => { 
        await remove(ids); 
      }, 
      confirmMessage: tCrud('deleteConfirm', { 
        count: selectedIds.length, 
        entity: tEntities('department').toLowerCase() 
      }), 
      confirmTitle: tCrud('deleteEntity', { entity: tEntities('departments') }) 
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

  const handleImport = async () => {
    refetch();
  };

  return (
    <AdminLayout>
      <PageHeader
        title={tManagement('title', { entity: tEntities('departments') })}
        description={tManagement('description', { entity: tEntities('departments').toLowerCase() })}
        breadcrumbs={[
          { label: tCommon('admin'), href: ADMIN_ROUTES.DASHBOARD },
          { label: tEntities('departments'), isActive: true },
        ]}
        action={
          <Button onClick={handleCreate} type="button">
            <Plus className="mr-2 h-4 w-4" /> {tCrud('createEntity', { entity: tEntities('department') })}
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
            storageKey="department-filters" 
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
            const department = data.find(d => d.id === id);
            if (department) handleEdit(department);
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

      {/* Create/Edit Department Modal */}
      {/* Create/Edit Department Modal */}
      <Dialog open={formDialogOpen} onOpenChange={setFormDialogOpen}>
        <DialogContent className="max-w-4xl max-h-[90vh] p-0 flex flex-col gap-0 overflow-hidden">
          {/* Header - Fixed */}
          <div className="shrink-0 px-6 pt-6 pb-4 border-b bg-background">
            <DialogHeader>
              <DialogTitle>
                {editingDepartment 
                  ? tCrud('editEntity', { entity: tEntities('department') }) 
                  : tCrud('createEntity', { entity: tEntities('department') })
                }
              </DialogTitle>
              <DialogDescription>
                {editingDepartment 
                  ? tCrud('editDescription', { entity: tEntities('department').toLowerCase() }) 
                  : tCrud('createDescription', { entity: tEntities('department').toLowerCase() })
                }
              </DialogDescription>
            </DialogHeader>
          </div>
          
          {/* Body - Scrollable */}
          <div className="flex-1 overflow-y-auto min-h-0">
            <div className="px-6 py-4">
              <DepartmentForm
                key={editingDepartment?.id || 'create'}
                initialData={editingDepartment}
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

      {/* Delete Confirmation Modal */}
      <ConfirmDialog
        open={deleteDialogOpen}
        onOpenChange={setDeleteDialogOpen}
        title={tCrud('deleteEntity', { entity: tEntities('department') })}
        description={tCrud('deleteConfirm', { 
          count: deleteIds.length, 
          entity: tEntities('department').toLowerCase() 
        })}
        onConfirm={confirmDelete}
        confirmText={tCommon('delete')}

        variant="destructive"
        isLoading={isDeleteProcessing}
      />
    </AdminLayout>
  );
}
