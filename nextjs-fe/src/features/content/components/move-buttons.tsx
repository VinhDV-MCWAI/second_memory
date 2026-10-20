'use client';

import { ArrowDown, ArrowUp } from 'lucide-react';
import { useTranslations } from 'next-intl';
import { Button } from '@/components/ui/button';
import { useMoveRow } from '@/features/content/hooks/use-move-row';
import { usePermissions } from '@/shared/hooks/use-permissions';
import { featureOfEndpoint, permissionName } from '@/shared/utils/permissions';

interface MoveButtonsProps {
  endpoint: string;
  id: number;
  /** First or last among its siblings: that arrow does nothing */
  first: boolean;
  last: boolean;
}

/** ↑ ↓ among siblings instead of typed order numbers (ADR-0022). Hidden without `{feature}.move`. */
export function MoveButtons({ endpoint, id, first, last }: MoveButtonsProps) {
  const t = useTranslations('content');
  const { can } = usePermissions();
  const move = useMoveRow(endpoint);

  if (!can(permissionName(featureOfEndpoint(endpoint), 'move'))) {
    return null;
  }

  return (
    <span className="inline-flex gap-1">
      <Button
        type="button"
        variant="ghost"
        size="icon"
        className="h-7 w-7"
        disabled={first || move.isPending}
        aria-label={t('moveUp')}
        onClick={() => move.mutate({ id, direction: 'up' })}
      >
        <ArrowUp className="h-4 w-4" />
      </Button>
      <Button
        type="button"
        variant="ghost"
        size="icon"
        className="h-7 w-7"
        disabled={last || move.isPending}
        aria-label={t('moveDown')}
        onClick={() => move.mutate({ id, direction: 'down' })}
      >
        <ArrowDown className="h-4 w-4" />
      </Button>
    </span>
  );
}
