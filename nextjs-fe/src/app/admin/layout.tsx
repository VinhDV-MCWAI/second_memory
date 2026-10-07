import type { ReactNode } from 'react';
import { AdminLayout } from '@/components/layout/admin-layout';

// Shared shell (auth guard, sidebar, header) for every /admin page; stays mounted across navigation.
export default function AdminRootLayout({ children }: { children: ReactNode }) {
  return <AdminLayout>{children}</AdminLayout>;
}
