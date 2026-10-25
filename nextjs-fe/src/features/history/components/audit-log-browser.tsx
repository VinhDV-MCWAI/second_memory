'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { Badge } from '@/components/ui/badge';
import { DataTable, type Column } from '@/components/common/data-table/data-table';
import { Pagination } from '@/components/common/data-table/pagination';
import { FilterPanel, type FilterField } from '@/components/common/data-table/filter-panel';
import { Changes } from '@/features/history/components/history-viewer';
import { useApiData } from '@/shared/hooks/use-api-data';
import { usePermissions } from '@/shared/hooks/use-permissions';
import { API_ENDPOINTS } from '@/shared/api/endpoints';
import { AuditEvent } from '@/shared/enums';
import { AUDITABLE_TYPES, PAGINATION, SORT_ORDER, type SortOrder } from '@/shared/config';
import { formatDateForBackend } from '@/shared/utils/date-formatter';
import type { AdminMst, FilterValue } from '@/shared/types/api';
import type { AuditLogEntry } from '@/shared/types/models';

/** Admins fit on one page for the "by" filter: a handful per installation. */
const ADMIN_PAGE_SIZE = 100;

/** Filters sent as dates: the date input gives `YYYY-MM-DD`, the API wants `d/m/Y`. */
const DATE_FILTERS = ['from_date', 'to_date'];

function toApiFilters(filters: Record<string, FilterValue>): Record<string, FilterValue> {
  return Object.fromEntries(
    Object.entries(filters)
      .filter(([, value]) => value !== undefined && value !== '')
      .map(([key, value]) => [
        key,
        DATE_FILTERS.includes(key) ? formatDateForBackend(String(value)) : value,
      ]),
  );
}

/** Every audit log row (ADR-0006), newest first, with filters; read-only. */
export function AuditLogBrowser() {
  const t = useTranslations('auditLog');
  const tHistory = useTranslations('history');
  const { can } = usePermissions();
  const [page, setPage] = useState<number>(PAGINATION.DEFAULT_PAGE);
  const [perPage, setPerPage] = useState<number>(PAGINATION.DEFAULT_PER_PAGE);
  const [filters, setFilters] = useState<Record<string, FilterValue>>({});
  const [sortBy, setSortBy] = useState('id');
  const [sortOrder, setSortOrder] = useState<SortOrder>(SORT_ORDER.DESC);

  const { data, loading, pagination } = useApiData<AuditLogEntry>(API_ENDPOINTS.AUDIT.LOG, {
    page,
    per_page: perPage,
    filters: toApiFilters(filters),
    sort_by: sortBy,
    sort_order: sortOrder,
  });
  const { data: admins } = useApiData<AdminMst>(API_ENDPOINTS.MASTER.ADMIN, {
    per_page: ADMIN_PAGE_SIZE,
    enabled: can('admin-mst.list'),
  });

  const applyFilters = (next: Record<string, FilterValue>) => {
    setFilters(next);
    setPage(PAGINATION.DEFAULT_PAGE);
  };

  const handleSort = (column: string) => {
    setPage(PAGINATION.DEFAULT_PAGE);
    if (sortBy === column) {
      setSortOrder(sortOrder === SORT_ORDER.ASC ? SORT_ORDER.DESC : SORT_ORDER.ASC);
    } else {
      setSortBy(column);
      setSortOrder(SORT_ORDER.DESC);
    }
  };

  const typeLabel = (type: string) =>
    (AUDITABLE_TYPES as readonly string[]).includes(type) ? t(`types.${type}`) : type;

  const columns: Column<AuditLogEntry>[] = [
    {
      key: 'created_at',
      label: t('time'),
      sortable: true,
      render: (entry) => (
        <time dateTime={entry.created_at}>{new Date(entry.created_at).toLocaleString()}</time>
      ),
    },
    {
      key: 'event',
      label: t('event'),
      render: (entry) => <Badge variant="outline">{tHistory(`events.${entry.event}`)}</Badge>,
    },
    {
      key: 'auditable_type',
      label: t('record'),
      render: (entry) =>
        `${typeLabel(entry.auditable_type)}${entry.auditable_id ? ` #${entry.auditable_id}` : ''}`,
    },
    {
      key: 'actor_user_name',
      label: t('actor'),
      render: (entry) => entry.actor_user_name ?? '—',
    },
    { key: 'ip_address', label: t('ipAddress'), render: (entry) => entry.ip_address ?? '—' },
    {
      key: 'changes',
      label: t('changes'),
      render: (entry) => {
        const count = Object.keys({ ...entry.old_values, ...entry.new_values }).length;
        if (count === 0) return '—';
        return (
          <details>
            <summary className="cursor-pointer">{t('fieldCount', { count })}</summary>
            <Changes entry={entry} />
          </details>
        );
      },
    },
  ];

  const filterFields: FilterField[] = [
    {
      key: 'auditable_type',
      label: t('type'),
      type: 'select',
      options: AUDITABLE_TYPES.map((type) => ({ value: type, label: t(`types.${type}`) })),
    },
    {
      key: 'event',
      label: t('event'),
      type: 'select',
      options: Object.values(AuditEvent).map((event) => ({
        value: event,
        label: tHistory(`events.${event}`),
      })),
    },
    ...(admins.length > 0
      ? [
          {
            key: 'admin_mst_id',
            label: t('actor'),
            type: 'select' as const,
            options: admins.map((admin) => ({ value: admin.id, label: admin.user_name })),
          },
        ]
      : []),
    { key: 'from_date', label: t('fromDate'), type: 'date' },
    { key: 'to_date', label: t('toDate'), type: 'date' },
  ];

  return (
    <div className="mt-6 space-y-4">
      <FilterPanel
        filters={filters}
        onFilterChange={(next) => applyFilters(next as Record<string, FilterValue>)}
        onReset={() => applyFilters({})}
        fields={filterFields}
      />

      <DataTable
        data={data}
        columns={columns}
        loading={loading}
        onSort={handleSort}
        sortBy={sortBy}
        sortOrder={sortOrder}
        showActions={false}
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
  );
}
