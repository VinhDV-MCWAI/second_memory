'use client';

import { useTranslations } from 'next-intl';
import { ResourceListPage } from '@/components/common/resource-list-page';
import type { Column } from '@/components/common/data-table/data-table';
import type { FilterField } from '@/components/common/data-table/filter-panel';
import { Badge } from '@/components/ui/badge';
import { LearningGoalForm } from '@/features/ledger/components/learning-goal-form';
import { API_ENDPOINTS } from '@/shared/api';
import { GoalStatus, GoalStatusLabels, SkillLevel, SkillLevelLabels } from '@/shared/enums';
import { enumOptions } from '@/shared/utils/enum-options';
import type { LearningGoal } from '@/shared/types/models';
import type { SearchField } from '@/shared/types/data-table.types';

export default function LearningGoalListPage() {
  const tFields = useTranslations('fields');

  const columns: Column<LearningGoal>[] = [
    { key: 'skill', label: tFields('skill'), render: (item) => item.skill.name },
    {
      key: 'target_level',
      label: tFields('targetLevel'),
      sortable: true,
      render: (item) =>
        `${SkillLevelLabels[item.skill.current_level as SkillLevel]} → ${item.target_level_label}`,
    },
    { key: 'target_date', label: tFields('targetDate'), sortable: true },
    {
      key: 'status',
      label: tFields('status'),
      sortable: true,
      render: (item) => (
        <Badge variant={item.status === GoalStatus.OPEN ? 'default' : 'secondary'}>
          {GoalStatusLabels[item.status as GoalStatus]}
        </Badge>
      ),
    },
    { key: 'achieved_on', label: tFields('achievedOn') },
  ];

  const filterFields: FilterField[] = [
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: enumOptions(GoalStatusLabels),
    },
  ];

  const searchFields: SearchField[] = [
    {
      key: 'status',
      label: tFields('status'),
      type: 'select',
      options: enumOptions(GoalStatusLabels),
    },
  ];

  return (
    <ResourceListPage
      endpoint={API_ENDPOINTS.LEDGER.LEARNING_GOAL}
      entity={{ one: 'goal', many: 'goals' }}
      columns={columns}
      filterFields={filterFields}
      searchFields={searchFields}
      defaultSortBy="target_date"
      filtersKey="goal-filters"
      form={LearningGoalForm}
      dialogClassName="max-w-xl"
    />
  );
}
