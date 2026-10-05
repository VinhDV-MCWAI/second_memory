'use client';

import { useState, useRef } from 'react';
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
import type { CategoryMgmt } from '@/shared/types/api';
import { API_ENDPOINTS } from '@/shared/api';
import { 
  SORT_ORDER, 
  type SortOrder, 
  PAGINATION, 
  ADMIN_ROUTES,
  UI_CONSTANTS 
} from '@/shared/config';
import { IsActive, IsActiveLabels } from '@/shared/enums/enums';
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
import { CategoryForm } from '@/components/forms/category-form';
import { useTranslations } from 'next-intl';

export default function CategoryListPage() {
  const [page, setPage] = useState<number>(PAGINATION.DEFAULT_PAGE);
  const [perPage, setPerPage] = useState<number>(PAGINATION.DEFAULT_PER_PAGE);
  const [filters, setFilters] = useState({});
  const [sortBy, setSortBy] = useState('rank_order');
  const [sortOrder, setSortOrder] = useState<SortOrder>(SORT_ORDER.ASC);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  
  // Dialog states
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [deleteIds, setDeleteIds] = useState<number[]>([]);
  const [formDialogOpen, setFormDialogOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState<CategoryMgmt | null>(null);

  const tCommon = useTranslations('common');
  const tEntities = useTranslations('entities');
  const tFields = useTranslations('fields');
  const tManagement = useTranslations('management');
  const tCrud = useTranslations('crud');
  const tBulkActions = useTranslations('bulkActions');

  const { data, loading, pagination, refetch } = useApiData<CategoryMgmt>(
    API_ENDPOINTS.MANAGEMENT.CATEGORY,
    { page, per_page: perPage, filters, sort_by: sortBy, sort_order: sortOrder }
  );

  const { remove } = useCrud<CategoryMgmt>(API_ENDPOINTS.MANAGEMENT.CATEGORY);
  
  // Ref to trigger form submission from outside (for EDIT mode in Dialog)
  const submitTriggerRef = useRef<(() => void) | null>(null);

  const handleCreate = () => {
    setEditingCategory(null);
    setFormDialogOpen(true);
  };

  const handleEdit = (category: CategoryMgmt) => {
    setEditingCategory(category);
    setFormDialogOpen(true);
  };
  
  const handleDialogUpdate = () => {
    if (submitTriggerRef.current) {
      submitTriggerRef.current();
    }
  };

  const handleFormSuccess = () => {
    setFormDialogOpen(false);
    refetch(); // Refetch the list after successful update
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

  const columns: Column<CategoryMgmt>[] = [
    { key: 'id', label: tFields('id'), sortable: true },
    { key: 'rank_order', label: tFields('order'), sortable: true },
    { key: 'name', label: tFields('name'), sortable: true },
    { key: 'description', label: tFields('description') },
    { key: 'icon', label: tFields('icon') },
    {
      key: 'status',
      label: tFields('status'),
      sortable: true,
      render: (category) => (
        <Badge variant={category.status === IsActive.TRUE ? 'default' : 'secondary'}>
          {IsActiveLabels[category.status as IsActive]}
        </Badge>
      ),
    },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    { key: 'name', label: tFields('name'), type: 'text', placeholder: tCommon('search') },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: [
        { value: IsActive.TRUE, label: IsActiveLabels[IsActive.TRUE] },
        { value: IsActive.FALSE, label: IsActiveLabels[IsActive.FALSE] },
      ],
    },
    { key: 'is_active', label: tCommon('active'), type: 'boolean' },
  ];
  const searchFields: SearchField[] = [
    { key: 'name', label: tFields('name'), type: 'text' },
    { key: 'description', label: tFields('description'), type: 'text' },
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: Object.entries(IsActiveLabels).map(([value, label]) => ({
        value: value.toString(),
        label
      }))
    },
    { key: 'created_at', label: tFields('createdAt'), type: 'date' }
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
        entity: tEntities('category').toLowerCase() 
      }), 
      confirmTitle: tCrud('deleteEntity', { entity: tEntities('categories') }) 
    }, 
    { 
      label: tBulkActions('activateSelected'), 
      icon: <CheckCircle className="h-4 w-4" />, 
      onClick: async () => { 
        refetch(); 
      } 
    }, 
    { 
      label: tBulkActions('deactivateSelected'), 
      icon: <XCircle className="h-4 w-4" />, 
      onClick: async () => { 
        refetch(); 
      } 
    }
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
        title={tManagement('title', { entity: tEntities('categories') })}
        description={tManagement('description', { entity: tEntities('categories').toLowerCase() })}
        breadcrumbs={[
          { label: tCommon('admin'), href: ADMIN_ROUTES.DASHBOARD },
          { label: tEntities('categories'), isActive: true },
        ]}
        action={
          <Button onClick={handleCreate}>
            <Plus className="mr-2 h-4 w-4" /> {tCrud('createEntity', { entity: tEntities('category') })}
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
            storageKey="category-filters" 
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
            const category = data.find(c => c.id === id);
            if (category) handleEdit(category);
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

      {/* Create/Edit Category Modal */}
      <Dialog open={formDialogOpen} onOpenChange={setFormDialogOpen}>
        <DialogContent className="max-w-4xl max-h-[90vh] p-0 flex flex-col gap-0 overflow-hidden">
          {/* Header - Fixed */}
          <div className="shrink-0 px-6 pt-6 pb-4 border-b bg-background">
            <DialogHeader>
              <DialogTitle>{editingCategory ? tCrud('editEntity', { entity: tEntities('category') }) : tCrud('createEntity', { entity: tEntities('category') })}</DialogTitle>
              <DialogDescription>
                {editingCategory ? tCrud('editDescription', { entity: tEntities('category').toLowerCase() }) : tCrud('createDescription', { entity: tEntities('category').toLowerCase() })}
              </DialogDescription>
            </DialogHeader>
          </div>
          
          {/* Body - Scrollable */}
          <div className="flex-1 overflow-y-auto min-h-0">
            <div className="px-6 py-4">
              <CategoryForm
                initialData={editingCategory}
                onSuccess={handleFormSuccess}
                onCancel={() => setFormDialogOpen(false)}
                renderActions={false}  // Never render actions inside form when in dialog
                hideActions={true}  // Hide any action buttons
                submitTriggerRef={editingCategory ? submitTriggerRef : undefined}  // Pass ref for EDIT mode
              />
            </div>
          </div>
          
          {/* Footer - Fixed */}
          <div className="shrink-0 px-6 py-4 border-t bg-muted/20">
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setFormDialogOpen(false)}>
                {tCommon('cancel')}
              </Button>
              {editingCategory ? (
                <button
                  type="button"
                  onPointerDown={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    handleDialogUpdate();
                  }}
                  disabled={false}
                  className="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-4 py-2"
                >
                  {tCommon('update')}
                </button>
              ) : (
                <Button
                  type="button"
                  onClick={() => {
                    // Trigger form submission for create mode
                    // The form is a native form element, so we need to trigger its submit
                    const form = document.querySelector('form');
                    if (form) {
                      form.requestSubmit();
                    }
                  }}
                  className="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-4 py-2"
                >
                  {tCommon('create')}
                </Button>
              )}
            </DialogFooter>
          </div>
        </DialogContent>
      </Dialog>

      <ConfirmDialog
        open={deleteDialogOpen}
        onOpenChange={setDeleteDialogOpen}
        title={tCrud('deleteEntity', { entity: tEntities('category') })}
        description={tCrud('deleteConfirm', { count: deleteIds.length, entity: tEntities('category').toLowerCase() })}
        onConfirm={confirmDelete}
        confirmText={tCommon('delete')}

        variant="destructive"
        isLoading={isDeleteProcessing}
      />
    </AdminLayout>
  );
}
