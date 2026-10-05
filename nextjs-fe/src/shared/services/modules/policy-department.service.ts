import { ENDPOINTS } from '@/shared/api';
import type { PolicyDepartmentMst } from '@/shared/types/api';
import { createCrudService } from '../factories';

export const policyDepartmentService = createCrudService<PolicyDepartmentMst>({
  endpoint: ENDPOINTS.MASTER.POLICY_DEPARTMENT,
  useSuffix: true,
});
