'use client';

import { useState } from 'react';
import { useApiData } from '@/shared/hooks/useApiData';
import { useCrud } from '@/shared/hooks/useCrud';
import { useActionLock } from '@/shared/hooks/useActionLock';
import { PageHeader } from '@/components/layout/page-header';
import { DataTable, type Column } from '@/components/common/data-table/data-table';
import { Pagination } from '@/components/common/data-table/pagination';
import { FilterPanel, type FilterField } from '@/components/common/data-table/filter-panel';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { Button } from '@/components/ui/button';
import type { PolicyDepartmentMst } from '@/shared/types/api';
import { API_ENDPOINTS } from '@/shared/api';
import {
  SORT_ORDER,
  SORT_FIELDS,
  type SortOrder,
  PAGINATION,
  ADMIN_ROUTES,
  UI_CONSTANTS,
} from '@/shared/config';
import { AdvancedSearch } from '@/components/common/advanced-search';
import type { SearchField, SearchCriteria } from '@/shared/types/data-table.types';
import { SavedFilters } from '@/components/common/saved-filters';
import { BulkActions, type BulkAction } from '@/components/common/bulk-actions';
import { ImportExport } from '@/components/common/import-export';
import { Trash2, Plus } from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { PolicyDepartmentForm } from '@/components/forms/policy-department-form';
import { useTranslations } from 'next-intl';

export default function PolicyDepartmentListPage() {
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
  const [editingPolicy, setEditingPolicy] = useState<PolicyDepartmentMst | null>(null);

  const tCommon = useTranslations('common');
  const tEntities = useTranslations('entities');
  const tFields = useTranslations('fields');
  const tManagement = useTranslations('management');
  const tCrud = useTranslations('crud');
  const tBulkActions = useTranslations('bulkActions');

  const { data, loading, pagination, refetch } = useApiData<PolicyDepartmentMst>(
    API_ENDPOINTS.MASTER.POLICY_DEPARTMENT,
    { page, per_page: perPage, filters, sort_by: sortBy, sort_order: sortOrder },
  );

  const { remove } = useCrud<PolicyDepartmentMst>(API_ENDPOINTS.MASTER.POLICY_DEPARTMENT);

  const handleCreate = () => {
    setEditingPolicy(null);
    setFormDialogOpen(true);
  };

  const handleEdit = (policy: PolicyDepartmentMst) => {
    setEditingPolicy(policy);
    setFormDialogOpen(true);
  };

  const handleFormSuccess = () => {
    setFormDialogOpen(false);
  };

  const handleDelete = async (ids: number[]) => {
    setDeleteIds(ids);
    setDeleteDialogOpen(true);
  };

  const { execute, isLoading: isDeleteProcessing } = useActionLock({
    delay: UI_CONSTANTS.ACTION_DELAY_MS,
  });

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

  const columns: Column<PolicyDepartmentMst>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'table_name', label: tFields('tableName'), sortable: true },
    { key: 'row_id', label: tFields('rowId'), sortable: true },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    {
      key: 'table_name',
      label: tFields('tableName'),
      type: 'text',
      placeholder: tCommon('search'),
    },
  ];
  const searchFields: SearchField[] = [
    { key: 'table_name', label: tFields('tableName'), type: 'text' },
    { key: 'row_id', label: tFields('rowId'), type: 'number' },
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
        entity: tEntities('policyDepartment').toLowerCase(),
      }),
      confirmTitle: tCrud('deleteEntity', { entity: tEntities('policyDepartments') }),
    },
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
    <>
      <PageHeader
        title={tManagement('title', { entity: tEntities('policyDepartments') })}
        description={tManagement('description', {
          entity: tEntities('policyDepartments').toLowerCase(),
        })}
        breadcrumbs={[
          { label: tCommon('admin'), href: ADMIN_ROUTES.DASHBOARD },
          { label: tEntities('policyDepartments'), isActive: true },
        ]}
        action={
          <Button onClick={handleCreate}>
            <Plus className="mr-2 h-4 w-4" />{' '}
            {tCrud('createEntity', { entity: tEntities('policyDepartment') })}
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
            storageKey="policy-department-filters"
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

        <BulkActions
          selectedIds={selectedIds}
          onClearSelection={() => setSelectedIds([])}
          actions={bulkActions}
          isLoading={loading}
        />

        <DataTable
          data={data}
          columns={columns}
          loading={loading}
          selectedIds={selectedIds}
          onSelectionChange={setSelectedIds}
          onSort={handleSort}
          sortBy={sortBy}
          sortOrder={sortOrder}
          onEdit={(id) => {
            const policy = data.find((p) => p.id === id);
            if (policy) handleEdit(policy);
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

      {/* Create/Edit Policy Department Modal */}
      {/* Create/Edit Policy Department Modal */}
      <Dialog open={formDialogOpen} onOpenChange={setFormDialogOpen}>
        <DialogContent className="flex max-h-[90vh] max-w-xl flex-col gap-0 overflow-hidden p-0">
          {/* Header - Fixed */}
          <div className="shrink-0 border-b bg-background px-6 pt-6 pb-4">
            <DialogHeader>
              <DialogTitle>
                {editingPolicy
                  ? tCrud('editEntity', { entity: tEntities('policyDepartment') })
                  : tCrud('createEntity', { entity: tEntities('policyDepartment') })}
              </DialogTitle>
              <DialogDescription>
                {editingPolicy
                  ? tCrud('editDescription', {
                      entity: tEntities('policyDepartment').toLowerCase(),
                    })
                  : tCrud('createDescription', {
                      entity: tEntities('policyDepartment').toLowerCase(),
                    })}
              </DialogDescription>
            </DialogHeader>
          </div>

          {/* Body - Scrollable */}
          <div className="min-h-0 flex-1 overflow-y-auto">
            <div className="px-6 py-4">
              <PolicyDepartmentForm
                initialData={editingPolicy}
                onSuccess={handleFormSuccess}
                onCancel={() => setFormDialogOpen(false)}
              />
            </div>
          </div>

          {/* Footer - Fixed */}
          <div className="shrink-0 border-t bg-muted/20 px-6 py-4">
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
        title={tCrud('deleteEntity', { entity: tEntities('policyDepartment') })}
        description={tCrud('deleteConfirm', {
          count: deleteIds.length,
          entity: tEntities('policyDepartment').toLowerCase(),
        })}
        onConfirm={confirmDelete}
        confirmText={tCommon('delete')}
        variant="destructive"
        isLoading={isDeleteProcessing}
      />
    </>
  );
}
