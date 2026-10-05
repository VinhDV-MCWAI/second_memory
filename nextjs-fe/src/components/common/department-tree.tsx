'use client';

import { useState } from 'react';
import { ChevronRight, ChevronDown, Building2 } from 'lucide-react';
import { cn } from "@/shared/utils";
import { useTranslations } from 'next-intl';
import type { DepartmentMst } from '@/shared/types/models/master';
import type { DepartmentTreeProps, TreeNodeProps } from '@/shared/types/data-table.types';

function TreeNode({ department, childNodes, level, onSelect, selectedId }: TreeNodeProps) {
  const [isExpanded, setIsExpanded] = useState(true);
  const hasChildren = childNodes.length > 0;
  const isSelected = selectedId === department.id;

  return (
    <div>
      <div
        className={cn(
          'flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-muted cursor-pointer transition-colors',
          isSelected && 'bg-primary/10 text-primary font-medium'
        )}
        style={{ paddingLeft: `${level * 1.5}rem` }}
        onClick={() => onSelect?.(department)}
      >
        {hasChildren ? (
          <button
            onClick={(e) => {
              e.stopPropagation();
              setIsExpanded(!isExpanded);
            }}
            className="p-0.5 hover:bg-muted-foreground/10 rounded"
          >
            {isExpanded ? (
              <ChevronDown className="h-4 w-4" />
            ) : (
              <ChevronRight className="h-4 w-4" />
            )}
          </button>
        ) : (
          <div className="w-5" />
        )}
        
        <Building2 className="h-4 w-4 text-muted-foreground" />
        
        <span className="flex-1">{department.name}</span>
        
        {department.description && (
          <span className="text-xs text-muted-foreground hidden md:block">
            {department.description}
          </span>
        )}
      </div>

      {hasChildren && isExpanded && (
        <div>
          {childNodes.map((child) => {
            const grandChildren = buildTree(child.children || []);
            return (
              <TreeNode
                key={child.id}
                department={child}
                childNodes={grandChildren}
                level={level + 1}
                onSelect={onSelect}
                selectedId={selectedId}
              />
            );
          })}
        </div>
      )}
    </div>
  );
}

function buildTree(departments: DepartmentMst[]): DepartmentMst[] {
  return departments;
}

export function DepartmentTree({
  departments,
  onSelect,
  selectedId,
  className,
}: DepartmentTreeProps) {
  const t = useTranslations('common');
  const rootDepartments = departments.filter((dept) => !dept.parent_id);

  if (departments.length === 0) {
    return (
      <div className="flex h-32 items-center justify-center text-sm text-muted-foreground">
        {t('noData')}
      </div>
    );
  }

  return (
    <div className={cn('space-y-1', className)}>
      {rootDepartments.map((dept) => {
        const childNodes = dept.children || [];
        return (
          <TreeNode
            key={dept.id}
            department={dept}
            childNodes={childNodes}
            level={0}
            onSelect={onSelect}
            selectedId={selectedId}
          />
        );
      })}
    </div>
  );
}
