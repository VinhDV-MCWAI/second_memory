import { ENDPOINTS } from '@/shared/api';
import type { CategoryMgmt } from '@/shared/types/api';
import { createCrudService } from '../factories';

export const categoryService = createCrudService<CategoryMgmt>({
  endpoint: ENDPOINTS.MANAGEMENT.CATEGORY,
});
