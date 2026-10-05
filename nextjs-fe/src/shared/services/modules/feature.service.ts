import { ENDPOINTS } from '@/shared/api';
import type { FeatureMst } from '@/shared/types/api';
import { createCrudService } from '../factories';

export const featureService = createCrudService<FeatureMst>({
  endpoint: ENDPOINTS.MASTER.FEATURE,
});
