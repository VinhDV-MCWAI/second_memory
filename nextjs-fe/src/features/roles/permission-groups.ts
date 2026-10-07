import type { ApiMst, FeatureMst } from '@/shared/types/api';
import type { GroupedApisByFeature } from '@/features/roles/role-wizard.types';
import { API_TYPE_TO_METHOD, HTTP_METHODS } from '@/features/roles/role-wizard.constant';

/** Method filter value meaning "all methods"; other values are `HTTP_METHODS`. */
export const ALL_METHODS = '*';

export interface PermissionFilter {
  searchApi: string;
  searchFeature: string;
  method: string;
}

/** APIs grouped under their feature; APIs of unknown features are dropped. */
export function groupApisByFeature(features: FeatureMst[], apis: ApiMst[]): GroupedApisByFeature {
  const grouped: GroupedApisByFeature = {};
  features.forEach((feature) => {
    grouped[feature.id] = { feature, apis: [] };
  });
  apis.forEach((api) => {
    grouped[api.feature_mst_id]?.apis.push(api);
  });
  return grouped;
}

/**
 * Applies the feature name search, API name/path search and method filter. A feature with no
 * matching API stays visible only while no API filter is active.
 */
export function filterGroupedApis(
  grouped: GroupedApisByFeature,
  { searchApi, searchFeature, method }: PermissionFilter,
): GroupedApisByFeature {
  const result: GroupedApisByFeature = {};
  const apiQuery = searchApi.toLowerCase();
  const featureQuery = searchFeature.toLowerCase();

  Object.entries(grouped).forEach(([featureId, { feature, apis }]) => {
    if (searchFeature && !feature.name.toLowerCase().includes(featureQuery)) {
      return;
    }

    const filteredApis = apis.filter((api: ApiMst) => {
      if (
        searchApi &&
        !api.name.toLowerCase().includes(apiQuery) &&
        !api.path.toLowerCase().includes(apiQuery)
      ) {
        return false;
      }
      // Same mapping as the method badge: unknown types show as GET
      return (
        method === ALL_METHODS || (API_TYPE_TO_METHOD[api.type] || HTTP_METHODS.GET) === method
      );
    });

    if (filteredApis.length > 0 || (!searchApi && method === ALL_METHODS)) {
      result[parseInt(featureId)] = { feature, apis: filteredApis };
    }
  });

  return result;
}

/** Adds (`checked`) or removes `ids` from the selection, without duplicates. */
export function toggleIds(selected: number[], ids: number[], checked: boolean): number[] {
  return checked
    ? Array.from(new Set([...selected, ...ids]))
    : selected.filter((id) => !ids.includes(id));
}
