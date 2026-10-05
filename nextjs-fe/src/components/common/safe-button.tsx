import React from 'react';
import { Button } from '@/components/ui/button';
import { useActionLock } from '@/shared/hooks/useActionLock';
import { LoadingSpinner } from '@/components/ui/loading';
import type { SafeButtonProps } from '@/shared/types';

export const SafeButton = React.forwardRef<HTMLButtonElement, SafeButtonProps>(
  ({ onSafeClick, onClick, showLoading = true, disabled, children, ...props }, ref) => {
    const { isLoading, execute } = useActionLock();

    const handleClick = async (e: React.MouseEvent<HTMLButtonElement>) => {
      if (onSafeClick) {
        e.preventDefault();
        await execute(onSafeClick);
      } else if (onClick) {
        onClick(e);
      }
    };

    return (
      <Button
        ref={ref}
        disabled={disabled || isLoading}
        onClick={handleClick}
        {...props}
      >
        {isLoading && showLoading && <LoadingSpinner size="sm" />}
        {children}
      </Button>
    );
  }
);

SafeButton.displayName = 'SafeButton';
