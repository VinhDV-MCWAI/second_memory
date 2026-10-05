import { ENDPOINTS } from '@/shared/api';
import type { TokenMst } from '@/shared/types/api';
import { createCrudService } from '../factories';

export const tokenService = createCrudService<TokenMst>({
  endpoint: ENDPOINTS.MASTER.TOKEN,
  useSuffix: true,
});
