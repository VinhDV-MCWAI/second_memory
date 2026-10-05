import { ENDPOINTS } from '@/shared/api';
import type { SliderMgmt } from '@/shared/types/api';
import { createCrudService } from '../factories';

export const sliderService = createCrudService<SliderMgmt>({
  endpoint: ENDPOINTS.MANAGEMENT.SLIDER,
});
