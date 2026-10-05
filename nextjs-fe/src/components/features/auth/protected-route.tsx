'use client';

import type { ReactNode } from 'react';

interface ProtectedRouteProps {
  children: ReactNode;
}

export default function ProtectedRoute({ children }: ProtectedRouteProps) {
  // Auth check logic removed as per request.
  // This component now acts as a simple wrapper.
  // Future implementation should check for cookie existence or use middleware.

  return <>{children}</>;
}
