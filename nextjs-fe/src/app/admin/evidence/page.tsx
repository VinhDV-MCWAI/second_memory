'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { Badge } from '@/components/ui/badge';
import { EvidenceForm } from '@/features/ledger/components/evidence-form';
import { API_ENDPOINTS } from '@/shared/api';
import {
  EvidenceSource,
  EvidenceSourceLabels,
  EvidenceType,
  EvidenceTypeLabels,
} from '@/shared/enums';
import { enumOptions } from '@/shared/utils/enum-options';
import type { Evidence } from '@/shared/types/models';
import type { SearchField } from '@/shared/types/data-table.types';

export default function EvidenceListPage() {
  const tFields = useTranslations('fields');
  const tLedger = useTranslations('ledger');

  const columns: Column<Evidence>[] = [
    {
      key: 'title',
      label: tFields('title'),
      sortable: true,
      render: (item) => (
        <a href={item.url} target="_blank" rel="noopener noreferrer" className="underline">
          {item.title}
        </a>
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
      dialogClassName="max-w-3xl"
    />
  );
}
