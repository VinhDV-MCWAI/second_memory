'use client';

import { Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { Paperclip, X } from 'lucide-react';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { EvidenceForm } from '@/features/ledger/components/evidence-form';
import { API_ENDPOINTS } from '@/shared/api';
import { ADMIN_ROUTES, EVIDENCE_FILE_PARAMS } from '@/shared/config';
import {
  EvidenceSource,
  EvidenceSourceLabels,
  EvidenceType,
  EvidenceTypeLabels,
} from '@/shared/enums';
import { enumOptions } from '@/shared/utils/enum-options';
import type { Evidence } from '@/shared/types/models';
import type { SearchField } from '@/shared/types/data-table.types';

function EvidenceList() {
  const tFields = useTranslations('fields');
  const tLedger = useTranslations('ledger');
  const router = useRouter();
  const params = useSearchParams();
  // Opened from the Evidence column of the file screen (LEDGER-03)
  const mediaId = Number(params.get(EVIDENCE_FILE_PARAMS.ID)) || null;
  const fileName = params.get(EVIDENCE_FILE_PARAMS.NAME) ?? '';

  const columns: Column<Evidence>[] = [
    {
      key: 'title',
      label: tFields('title'),
      sortable: true,
      render: (item) => (
        <span className="flex items-center gap-2">
          {item.url ? (
            <a href={item.url} target="_blank" rel="noopener noreferrer" className="underline">
              {item.title}
            </a>
          ) : (
            <span>{item.title}</span>
          )}
          {item.files && item.files.length > 0 && (
            <span
              className="flex items-center gap-0.5 text-xs text-muted-foreground"
              title={tLedger('files.count', { count: item.files.length })}
            >
              <Paperclip className="h-3 w-3" aria-hidden />
              {item.files.length}
            </span>
          )}
        </span>
      ),
    },
    {
      key: 'type',
      label: tFields('type'),
      sortable: true,
      render: (item) => EvidenceTypeLabels[item.type as EvidenceType],
    },
    { key: 'occurred_on', label: tFields('occurredOn'), sortable: true },
    {
      key: 'skills',
      label: tFields('skills'),
      render: (item) => (
        <div className="flex flex-wrap gap-1">
          {item.skills?.map((skill) => (
            <Badge key={skill.id} variant="outline">
              {skill.name}
            </Badge>
          ))}
        </div>
      ),
    },
    {
      key: 'source',
      label: tFields('source'),
      render: (item) => EvidenceSourceLabels[item.source as EvidenceSource],
    },
    {
      key: 'is_public',
      label: tFields('visibility'),
      render: (item) => (
        <StatusBadge active={item.is_public && !item.unpublished_at}>
          {item.is_public && !item.unpublished_at ? tLedger('public') : tLedger('private')}
        </StatusBadge>
      ),
    },
  ];

  const filterFields: FilterField[] = [
    { key: 'title', label: tFields('title'), type: 'text' },
    {
      key: 'type',
      label: tFields('type'),
      type: 'select',
      options: enumOptions(EvidenceTypeLabels),
    },
    {
      key: 'source',
      label: tFields('source'),
      type: 'select',
      options: enumOptions(EvidenceSourceLabels),
    },
    { key: 'is_public', label: tLedger('public'), type: 'boolean' },
  ];

  const searchFields: SearchField[] = [{ key: 'title', label: tFields('title'), type: 'text' }];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.LEDGER.EVIDENCE}
      entity={{ one: 'evidence', many: 'evidenceMany' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="occurred_on"
      filtersKey="evidence-filters"
      form={EvidenceForm}
      dialogSize="xl"
      fixedFilters={mediaId ? { [EVIDENCE_FILE_PARAMS.ID]: mediaId } : undefined}
      toolbar={
        mediaId && (
          <div className="flex items-center gap-2 rounded-md border bg-muted/50 px-3 py-2 text-sm">
            <Paperclip className="h-4 w-4 text-muted-foreground" aria-hidden />
            <span className="truncate">{tLedger('files.filtered', { name: fileName })}</span>
            <Button
              variant="ghost"
              size="sm"
              className="ml-auto"
              onClick={() => router.replace(ADMIN_ROUTES.EVIDENCE)}
            >
              <X className="mr-1 h-4 w-4" />
              {tLedger('files.showAll')}
            </Button>
          </div>
        )
      }
    />
  );
}

export default function EvidenceListPage() {
  // useSearchParams needs a Suspense boundary in the App Router
  return (
    <Suspense>
      <EvidenceList />
    </Suspense>
  );
}
