'use client';

import { useState, type ComponentType, type ReactNode } from 'react';
import { useTranslations } from 'next-intl';
import { Plus, Trash2 } from 'lucide-react';
import { PageHeader } from '@/components/layout/page-header';
import { DataTable, type Column } from '@/components/common/data-table/data-table';
import { Pagination } from '@/components/common/data-table/pagination';
import { FilterPanel, type FilterField } from '@/components/common/data-table/filter-panel';
import { AdvancedSearch } from '@/components/common/advanced-search';
import { SavedFilters } from '@/components/common/saved-filters';
import { BulkActions, type BulkAction } from '@/components/common/bulk-actions';
import { AppDialog, type DialogAction, type DialogSize } from '@/components/common/app-dialog';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { Button } from '@/components/ui/button';
import { useApiData } from '@/shared/hooks/use-api-data';
import { useCrud } from '@/shared/hooks/use-crud';
import { useActionLock } from '@/shared/hooks/use-action-lock';
import { usePermissions } from '@/shared/hooks/use-permissions';
import { featureOfEndpoint, PERMISSION_ACTIONS, permissionName } from '@/shared/utils/permissions';
import {
  ADMIN_ROUTES,
  PAGINATION,
  SORT_ORDER,
  type SortOrder,
  UI_CONSTANTS,
} from '@/shared/config';
import type { FilterValue } from '@/shared/types/api';
import type { SearchCriteria, SearchField } from '@/shared/types/data-table.types';

/** What a form tells the dialog footer about itself. */
export interface ResourceFormState {
  /** Saving: every footer button is locked. */
  pending: boolean;
  /** Nothing can be saved (e.g. the owner role): the submit button is disabled. */
  submitDisabled?: boolean;
  /** Changed since the dialog opened: closing without saving asks first. */
  dirty?: boolean;
}

/** Props every entity form receives inside the standard create/edit dialog. */
export interface ResourceFormProps<T> {
  initialData?: T | null;
  /** Id for the `<form>`: the footer's create/update button submits it. */
  formId: string;
  onSuccess: () => void;
  onStateChange: (state: ResourceFormState) => void;
}

/** What a page's footer buttons get: the record being edited (null on create). */
export interface ResourceDialogContext<T> {
  record: T | null;
  close: () => void;
  /** The ready-made create/update button, to place where the page wants it. */
  submitAction: DialogAction;
}

/** A self-contained create/edit dialog (e.g. the role wizard) used instead of `form`. */
export interface ResourceEditorProps<T> {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  initialData?: T | null;
  onSuccess: () => void;
}

/** Id of the form inside the create/edit dialog. */
const FORM_ID = 'resource-form';

type Editor<T> =
  | {
      form: ComponentType<ResourceFormProps<T>>;
      dialogSize: DialogSize;
      /** Footer buttons after Cancel; without it the footer has only create/update. */
      dialogActions?: (context: ResourceDialogContext<T>) => DialogAction[];
      editor?: never;
    }
  | {
      editor: ComponentType<ResourceEditorProps<T>>;
      form?: never;
      dialogSize?: never;
      dialogActions?: never;
    };

/** What a page's own list view gets: the row buttons open the same dialogs as the table's. */
export interface ResourceViewActions<T> {
  /** Null without the update permission */
  edit: ((record: T) => void) | null;
  /** Null without the delete permission */
  remove: ((id: number) => void) | null;
}

type Listing<T> =
  | {
      columns: Column<T>[];
      filterFields: FilterField[];
      searchFields: SearchField[];
      defaultSortBy: string;
      /** Newest-first lists pass `SORT_ORDER.DESC`. */
      defaultSortOrder?: SortOrder;
      /** localStorage key of the saved filters. */
      filtersKey: string;
      staleTime?: number;
      /** Filters set by the page (e.g. from the URL), sent on top of the user's filters. */
      fixedFilters?: Record<string, FilterValue>;
      view?: never;
    }
  | {
      /** The page loads and shows its records itself (e.g. the category tree) instead of the table. */
      view: (actions: ResourceViewActions<T>) => ReactNode;
      columns?: never;
      filterFields?: never;
      searchFields?: never;
      defaultSortBy?: never;
      defaultSortOrder?: never;
      filtersKey?: never;
      staleTime?: never;
      fixedFilters?: never;
    };

export type ResourceListPageProps<T extends { id: number }> = Editor<T> &
  Listing<T> & {
    /** API resource, e.g. `API_ENDPOINTS.MASTER.ADMIN`. */
    endpoint: string;
    /** `entities.*` message keys for one and many records, e.g. `admin` / `admins`. */
    entity: { one: string; many: string };
    /** Shown above the search and filters, e.g. what the fixed filters mean. */
    toolbar?: ReactNode;
  };

/** Admin list page: header, search/filters, bulk delete, table, pagination, create/edit/delete dialogs. */
export function ResourceListPage<T extends { id: number }>({
  endpoint,
  entity,
  columns = [],
  filterFields = [],
  searchFields = [],
  defaultSortBy,
  defaultSortOrder = SORT_ORDER.ASC,
  filtersKey = '',
  staleTime,
  fixedFilters,
  toolbar,
  view,
  ...editor
}: ResourceListPageProps<T>) {
  const [page, setPage] = useState<number>(PAGINATION.DEFAULT_PAGE);
  const [perPage, setPerPage] = useState<number>(PAGINATION.DEFAULT_PER_PAGE);
  const [filters, setFilters] = useState<Record<string, FilterValue>>({});
  const [sortBy, setSortBy] = useState(defaultSortBy);
  const [sortOrder, setSortOrder] = useState<SortOrder>(defaultSortOrder);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [deleteIds, setDeleteIds] = useState<number[]>([]);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [formDialogOpen, setFormDialogOpen] = useState(false);
  const [editing, setEditing] = useState<T | null>(null);
  const [formState, setFormState] = useState<ResourceFormState>({ pending: false });

  const tCommon = useTranslations('common');
  const tEntities = useTranslations('entities');
  const tManagement = useTranslations('management');
  const tCrud = useTranslations('crud');
  const tBulkActions = useTranslations('bulkActions');
  const { can } = usePermissions();
  const feature = featureOfEndpoint(endpoint);
  const canCreate = can(permissionName(feature, PERMISSION_ACTIONS.STORE));
  const canUpdate = can(permissionName(feature, PERMISSION_ACTIONS.UPDATE));
  const canDelete = can(permissionName(feature, PERMISSION_ACTIONS.DELETE));
  const one = tEntities(entity.one);
  const many = tEntities(entity.many);

  const { data, loading, pagination } = useApiData<T>(endpoint, {
    page,
    per_page: perPage,
    filters: { ...filters, ...fixedFilters },
    sort_by: sortBy,
    sort_order: sortOrder,
    staleTime,
    enabled: !view,
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
    setFormState({ pending: false });
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
    // A new order starts from its first page
    setPage(PAGINATION.DEFAULT_PAGE);
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

  const submitAction: DialogAction = {
    key: 'submit',
    form: FORM_ID,
    permission: permissionName(
      feature,
      editing ? PERMISSION_ACTIONS.UPDATE : PERMISSION_ACTIONS.STORE,
    ),
    disabled: formState.submitDisabled,
    label: formState.pending
      ? tCommon(editing ? 'updating' : 'creating')
      : tCommon(editing ? 'update' : 'create'),
  };

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
          canCreate ? (
            <Button onClick={() => openEditor(null)}>
              <Plus className="mr-2 h-4 w-4" /> {tCrud('createEntity', { entity: one })}
            </Button>
          ) : undefined
        }
      />

      <div className="mt-6 space-y-4">
        {toolbar}
        {view ? (
          view({
            edit: canUpdate ? openEditor : null,
            remove: canDelete ? (id) => askDelete([id]) : null,
          })
        ) : (
          <>
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

            {canDelete && (
              <BulkActions
                selectedIds={selectedIds}
                onClearSelection={() => setSelectedIds([])}
                actions={bulkActions}
                isLoading={loading}
              />
            )}

            <DataTable
              data={data}
              columns={columns}
              loading={loading}
              selectedIds={selectedIds}
              onSelectionChange={canDelete ? setSelectedIds : undefined}
              onSort={handleSort}
              sortBy={sortBy}
              sortOrder={sortOrder}
              onEdit={
                canUpdate
                  ? (id) => {
                      const record = data.find((row) => row.id === id);
                      if (record) openEditor(record);
                    }
                  : undefined
              }
              onDelete={canDelete ? (id) => askDelete([id]) : undefined}
              showActions={canUpdate || canDelete}
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
          </>
        )}
      </div>

      {editor.editor ? (
        <editor.editor
          open={formDialogOpen}
          onOpenChange={setFormDialogOpen}
          initialData={editing}
          onSuccess={closeEditor}
        />
      ) : (
        <AppDialog
          open={formDialogOpen}
          onOpenChange={setFormDialogOpen}
          title={tCrud(editing ? 'editEntity' : 'createEntity', { entity: one })}
          description={tCrud(editing ? 'editDescription' : 'createDescription', {
            entity: one.toLowerCase(),
          })}
          size={editor.dialogSize}
          busy={formState.pending}
          dirty={formState.dirty}
          actions={
            editor.dialogActions?.({ record: editing, close: closeEditor, submitAction }) ?? [
              submitAction,
            ]
          }
        >
          <editor.form
            initialData={editing}
            formId={FORM_ID}
            onSuccess={closeEditor}
            onStateChange={setFormState}
          />
        </AppDialog>
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
