'use client';

import { useState, type ComponentType } from 'react';
import { useTranslations } from 'next-intl';
import { Plus, Trash2 } from 'lucide-react';
import { PageHeader } from '@/components/layout/page-header';
import { DataTable, type Column } from '@/components/common/data-table/data-table';
import { Pagination } from '@/components/common/data-table/pagination';
import { FilterPanel, type FilterField } from '@/components/common/data-table/filter-panel';
import { AdvancedSearch } from '@/components/common/advanced-search';
import { SavedFilters } from '@/components/common/saved-filters';
import { BulkActions, type BulkAction } from '@/components/common/bulk-actions';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { useApiData } from '@/shared/hooks/useApiData';
import { useCrud } from '@/shared/hooks/useCrud';
import { useActionLock } from '@/shared/hooks/useActionLock';
import {
  ADMIN_ROUTES,
  PAGINATION,
  SORT_ORDER,
  type SortOrder,
  UI_CONSTANTS,
} from '@/shared/config';
import type { FilterValue } from '@/shared/types/api';
import type { SearchCriteria, SearchField } from '@/shared/types/data-table.types';

/** Props every entity form receives inside the standard create/edit dialog. */
export interface ResourceFormProps<T> {
  initialData?: T | null;
  onSuccess: () => void;
  onCancel: () => void;
}

/** A self-contained create/edit dialog (e.g. the role wizard) used instead of `form`. */
export interface ResourceEditorProps<T> {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  initialData?: T | null;
  onSuccess: () => void;
}

type Editor<T> =
  | { form: ComponentType<ResourceFormProps<T>>; dialogClassName: string; editor?: never }
  | { editor: ComponentType<ResourceEditorProps<T>>; form?: never; dialogClassName?: never };

export type ResourceListPageProps<T extends { id: number }> = Editor<T> & {
  /** API resource, e.g. `API_ENDPOINTS.MANAGEMENT.BANNER`. */
  endpoint: string;
  /** `entities.*` message keys for one and many records, e.g. `banner` / `banners`. */
  entity: { one: string; many: string };
  columns: Column<T>[];
  filterFields: FilterField[];
  searchFields: SearchField[];
  defaultSortBy: string;
  /** localStorage key of the saved filters. */
  filtersKey: string;
  staleTime?: number;
};

/** Admin list page: header, search/filters, bulk delete, table, pagination, create/edit/delete dialogs. */
export function ResourceListPage<T extends { id: number }>({
  endpoint,
  entity,
  columns,
  filterFields,
  searchFields,
  defaultSortBy,
  filtersKey,
  staleTime,
  ...editor
}: ResourceListPageProps<T>) {
  const [page, setPage] = useState<number>(PAGINATION.DEFAULT_PAGE);
  const [perPage, setPerPage] = useState<number>(PAGINATION.DEFAULT_PER_PAGE);
  const [filters, setFilters] = useState<Record<string, FilterValue>>({});
  const [sortBy, setSortBy] = useState(defaultSortBy);
  const [sortOrder, setSortOrder] = useState<SortOrder>(SORT_ORDER.ASC);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [deleteIds, setDeleteIds] = useState<number[]>([]);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [formDialogOpen, setFormDialogOpen] = useState(false);
  const [editing, setEditing] = useState<T | null>(null);

  const tCommon = useTranslations('common');
  const tEntities = useTranslations('entities');
  const tManagement = useTranslations('management');
  const tCrud = useTranslations('crud');
  const tBulkActions = useTranslations('bulkActions');
  const one = tEntities(entity.one);
  const many = tEntities(entity.many);

  const { data, loading, pagination } = useApiData<T>(endpoint, {
    page,
    per_page: perPage,
    filters,
    sort_by: sortBy,
    sort_order: sortOrder,
    staleTime,
  });
  const { remove } = useCrud(endpoint);
  const { execute, isLoading: isDeleteProcessing } = useActionLock({
    delay: UI_CONSTANTS.ACTION_DELAY_MS,
  });

  const applyFilters = (next: Record<string, FilterValue>) => {
    setFilters(next);
    setPage(PAGINATION.DEFAULT_PAGE);
  };

  const openEditor = (record: T | null) => {
    setEditing(record);
    setFormDialogOpen(true);
  };

  const askDelete = (ids: number[]) => {
    setDeleteIds(ids);
    setDeleteDialogOpen(true);
  };

  const confirmDelete = async () => {
    try {
      await execute(async () => {
        await remove(deleteIds);
        setSelectedIds([]);
        setDeleteIds([]);
        setDeleteDialogOpen(false);
      });
    } catch {
      // useCrud already showed the error
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
        entity: one.toLowerCase(),
      }),
      confirmTitle: tCrud('deleteEntity', { entity: many }),
    },
  ];

  const handleAdvancedSearch = (criteria: SearchCriteria[]) =>
    applyFilters(Object.fromEntries(criteria.map((c) => [c.field, c.value as FilterValue])));

  const closeEditor = () => setFormDialogOpen(false);

  return (
    <>
      <PageHeader
        title={tManagement('title', { entity: many })}
        description={tManagement('description', { entity: many.toLowerCase() })}
        breadcrumbs={[
          { label: tCommon('admin'), href: ADMIN_ROUTES.DASHBOARD },
          { label: many, isActive: true },
        ]}
        action={
          <Button onClick={() => openEditor(null)}>
            <Plus className="mr-2 h-4 w-4" /> {tCrud('createEntity', { entity: one })}
          </Button>
        }
      />

      <div className="mt-6 space-y-4">
        <div className="flex gap-2">
          <AdvancedSearch fields={searchFields} onSearch={handleAdvancedSearch} />
          <SavedFilters
            currentFilters={filters}
            onApplyFilter={(f) => applyFilters(f as Record<string, FilterValue>)}
            storageKey={filtersKey}
          />
        </div>

        <FilterPanel
          filters={filters}
          onFilterChange={(f) => applyFilters(f as Record<string, FilterValue>)}
          onReset={() => applyFilters({})}
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
            const record = data.find((row) => row.id === id);
            if (record) openEditor(record);
          }}
          onDelete={(id) => askDelete([id])}
        />

        <Pagination
          pagination={pagination}
          page={page}
          onPageChange={setPage}
          perPage={perPage}
          onPerPageChange={(next) => {
            setPerPage(next);
            setPage(PAGINATION.DEFAULT_PAGE);
          }}
        />
      </div>

      {editor.editor ? (
        <editor.editor
          open={formDialogOpen}
          onOpenChange={setFormDialogOpen}
          initialData={editing}
          onSuccess={closeEditor}
        />
      ) : (
        <Dialog open={formDialogOpen} onOpenChange={setFormDialogOpen}>
          <DialogContent
            className={`flex max-h-[90vh] flex-col gap-0 overflow-hidden p-0 ${editor.dialogClassName}`}
          >
            <div className="shrink-0 border-b bg-background px-6 pt-6 pb-4">
              <DialogHeader>
                <DialogTitle>
                  {tCrud(editing ? 'editEntity' : 'createEntity', { entity: one })}
                </DialogTitle>
                <DialogDescription>
                  {tCrud(editing ? 'editDescription' : 'createDescription', {
                    entity: one.toLowerCase(),
                  })}
                </DialogDescription>
              </DialogHeader>
            </div>

            <div className="min-h-0 flex-1 overflow-y-auto">
              <div className="px-6 py-4">
                <editor.form initialData={editing} onSuccess={closeEditor} onCancel={closeEditor} />
              </div>
            </div>

            <div className="shrink-0 border-t bg-muted/20 px-6 py-4">
              <DialogFooter>
                <Button type="button" variant="outline" onClick={closeEditor}>
                  {tCommon('cancel')}
                </Button>
                <Button type="button" onClick={closeEditor}>
                  {tCommon('done')}
                </Button>
              </DialogFooter>
            </div>
          </DialogContent>
        </Dialog>
      )}

      <ConfirmDialog
        open={deleteDialogOpen}
        onOpenChange={setDeleteDialogOpen}
        title={tCrud('deleteEntity', { entity: one })}
        description={tCrud('deleteConfirm', { count: deleteIds.length, entity: one.toLowerCase() })}
        onConfirm={confirmDelete}
        confirmText={tCommon('delete')}
        variant="destructive"
        isLoading={isDeleteProcessing}
      />
    </>
  );
}
