import { ENDPOINTS } from '@/shared/api';
import type { BannerMgmt } from '@/shared/types/api';
import { createCrudService } from '../factories';

export const bannerService = createCrudService<BannerMgmt>({
  endpoint: ENDPOINTS.MANAGEMENT.BANNER,
});
