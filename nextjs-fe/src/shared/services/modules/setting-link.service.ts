import { ENDPOINTS } from '@/shared/api';
import type { SettingLinkMgmt } from '@/shared/types/api';
import { createCrudService } from '../factories';

export const settingLinkService = createCrudService<SettingLinkMgmt>({
  endpoint: ENDPOINTS.MANAGEMENT.SETTING_LINK,
  useSuffix: true,
});
