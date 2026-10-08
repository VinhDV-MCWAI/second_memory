'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import { StatusBadge } from '@/components/common/data-table/cells';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { Badge } from '@/components/ui/badge';
import { SkillForm } from '@/features/ledger/components/skill-form';
import { API_ENDPOINTS } from '@/shared/api';
import { SkillLevelLabels } from '@/shared/enums';
import { enumOptions } from '@/shared/utils/enum-options';
import type { Skill } from '@/shared/types/models';
import type { SearchField } from '@/shared/types/data-table.types';

export default function SkillListPage() {
  const tFields = useTranslations('fields');
  const tLedger = useTranslations('ledger');

  const columns: Column<Skill>[] = [
    { key: 'name', label: tFields('name'), sortable: true },
    { key: 'category', label: tFields('category'), sortable: true },
    {
      key: 'current_level',
      label: tFields('currentLevel'),
      sortable: true,
      render: (item) => item.current_level_label,
    },
    {
      key: 'tags',
      label: tFields('tags'),
      render: (item) => (
        <div className="flex flex-wrap gap-1">
          {item.tags?.map((tag) => (
            <Badge key={tag.id} variant="outline">
              {tag.name}
            </Badge>
          ))}
        </div>
      ),
    },
    {
      key: 'is_public',
      label: tFields('visibility'),
      render: (item) => (
        <StatusBadge active={item.is_public}>
          {item.is_public ? tLedger('public') : tLedger('private')}
        </StatusBadge>
      ),
    },
    { key: 'updated_at', label: tFields('updatedAt'), sortable: true },
  ];

  const filterFields: FilterField[] = [
    { key: 'name', label: tFields('name'), type: 'text' },
    { key: 'category', label: tFields('category'), type: 'text' },
    {
      key: 'current_level',
      label: tFields('currentLevel'),
      type: 'select',
      options: enumOptions(SkillLevelLabels),
    },
    { key: 'is_public', label: tLedger('public'), type: 'boolean' },
  ];

  const searchFields: SearchField[] = [
    { key: 'name', label: tFields('name'), type: 'text' },
    { key: 'category', label: tFields('category'), type: 'text' },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.LEDGER.SKILL}
      entity={{ one: 'skill', many: 'skills' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="name"
      filtersKey="skill-filters"
      form={SkillForm}
      dialogClassName="max-w-3xl"
    />
  );
}
