'use client';

import { useState, useMemo, useCallback } from 'react';
import { useTranslations } from 'next-intl';
import { useApiData } from '@/shared/hooks/useApiData';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { API_ENDPOINTS } from '@/shared/api';
import type { ApiMst, FeatureMst } from '@/shared/types/api';
import { Search } from 'lucide-react';
import {
  HTTP_METHODS,
  HTTP_METHOD_LABELS,
  API_TYPE_TO_METHOD,
} from '@/shared/config/role-wizard.constant';
import type {
  Step2PermissionSetupProps,
  GroupedApisByFeature,
} from '@/shared/types/role-wizard.types';

export function Step2PermissionSetup({
  selectedApiIds,
  onSelectedApisChange,
}: Step2PermissionSetupProps) {
  const tCommon = useTranslations('common');
  const tWizard = useTranslations('roleWizard');

  const [searchApi, setSearchApi] = useState('');
  const [searchFeature, setSearchFeature] = useState('');
  const [methodFilter, setMethodFilter] = useState<string>('*');

  // Fetch APIs
  const { data: allApis, loading: apisLoading } = useApiData<ApiMst>(API_ENDPOINTS.MASTER.API, {
    per_page: 1000,
  });

  // Fetch Features
  const { data: allFeatures, loading: featuresLoading } = useApiData<FeatureMst>(
    API_ENDPOINTS.MASTER.FEATURE,
    { per_page: 1000 },
  );

  // Group APIs by Feature
  const groupedApis = useMemo(() => {
    const grouped: GroupedApisByFeature = {};

    allFeatures.forEach((feature) => {
      grouped[feature.id] = {
        feature,
        apis: [],
      };
    });

    allApis.forEach((api) => {
      if (grouped[api.feature_mst_id]) {
        grouped[api.feature_mst_id].apis.push(api);
      }
    });

    return grouped;
  }, [allApis, allFeatures]);

  // Filter features and APIs
  const filteredGroupedApis = useMemo(() => {
    const result: GroupedApisByFeature = {};

    Object.entries(groupedApis).forEach(([featureId, { feature, apis }]) => {
      // Filter by feature name search
      if (searchFeature && !feature.name.toLowerCase().includes(searchFeature.toLowerCase())) {
        return;
      }

      // Filter APIs
      const filteredApis = apis.filter((api: ApiMst) => {
        // Filter by API search
        if (
          searchApi &&
          !api.name.toLowerCase().includes(searchApi.toLowerCase()) &&
          !api.path.toLowerCase().includes(searchApi.toLowerCase())
        ) {
          return false;
        }

        // Filter by method
        if (methodFilter !== '*' && api.type !== parseInt(methodFilter)) {
          return false;
        }

        return true;
      });

      // Only include feature if it has APIs or matches search
      if (filteredApis.length > 0 || (!searchApi && methodFilter === '*')) {
        result[parseInt(featureId)] = {
          feature,
          apis: filteredApis,
        };
      }
    });

    return result;
  }, [groupedApis, searchApi, searchFeature, methodFilter]);

  // Get highlighted features (those with checked APIs)
  const highlightedFeatures = useMemo(() => {
    const featured = new Set<number>();
    Object.entries(groupedApis).forEach(([featureId, { apis }]) => {
      const hasCheckedApi = apis.some((api: ApiMst) => selectedApiIds.includes(api.id));
      if (hasCheckedApi) {
        featured.add(parseInt(featureId));
      }
    });
    return featured;
  }, [groupedApis, selectedApiIds]);

  // Toggle feature checkbox
  const handleToggleFeature = useCallback(
    (featureId: number) => {
      const featureApis = groupedApis[featureId]?.apis || [];
      const featureApiIds = featureApis.map((api) => api.id);
      const allChecked = featureApiIds.every((id) => selectedApiIds.includes(id));

      if (allChecked) {
        // Uncheck all
        const newSelected = selectedApiIds.filter((id) => !featureApiIds.includes(id));
        onSelectedApisChange(newSelected);
      } else {
        // Check all
        const newSelected = Array.from(new Set([...selectedApiIds, ...featureApiIds]));
        onSelectedApisChange(newSelected);
      }
    },
    [groupedApis, selectedApiIds, onSelectedApisChange],
  );

  // Toggle API checkbox
  const handleToggleApi = useCallback(
    (apiId: number) => {
      if (selectedApiIds.includes(apiId)) {
        onSelectedApisChange(selectedApiIds.filter((id) => id !== apiId));
      } else {
        onSelectedApisChange([...selectedApiIds, apiId]);
      }
    },
    [selectedApiIds, onSelectedApisChange],
  );

  // Scroll feature into view from right panel
  const scrollFeatureIntoView = useCallback((featureId: number) => {
    const featureElement = document.getElementById(`feature-${featureId}`);
    if (featureElement) {
      featureElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }, []);

  const totalApis = allApis.length;
  const selectedCount = selectedApiIds.length;

  const isLoading = apisLoading || featuresLoading;

  return (
    <div className="flex h-full w-full flex-col space-y-4 overflow-hidden">
      {/* Main content with dual layout */}
      <div
        className="flex min-h-0 flex-1 gap-4 overflow-hidden rounded-lg border"
        style={{ minHeight: 0 }}
      >
        {/* Left panel - Features (30%) */}
        <div className="flex w-[30%] min-w-0 flex-col overflow-hidden border-r">
          {/* Search */}
          <div className="shrink-0 border-b p-3">
            <div className="relative">
              <Search className="absolute top-2.5 left-2 h-4 w-4 text-gray-400" />
              <Input
                placeholder={tWizard('searchFeature')}
                value={searchFeature}
                onChange={(e) => setSearchFeature(e.target.value)}
                className="pl-8 text-sm"
              />
            </div>
          </div>

          {/* Features list */}
          <div className="min-h-0 flex-1 overflow-y-auto">
            {isLoading ? (
              <div className="p-4 text-center text-sm text-gray-500">{tCommon('loading')}...</div>
            ) : (
              <div className="space-y-1 p-2">
                {Object.entries(filteredGroupedApis).map(([featureId, { feature }]) => {
                  const featureApis = groupedApis[parseInt(featureId)]?.apis || [];
                  const isHighlighted = highlightedFeatures.has(parseInt(featureId));

                  return (
                    <button
                      key={feature.id}
                      onClick={() => scrollFeatureIntoView(feature.id)}
                      className={`w-full rounded-md px-3 py-2 text-left text-sm font-medium transition-colors ${
                        isHighlighted
                          ? 'bg-blue-100 text-blue-900'
                          : 'text-gray-700 hover:bg-gray-100'
                      }`}
                    >
                      {feature.name}
                      {isHighlighted && (
                        <Badge className="ml-2 text-xs" variant="default">
                          {featureApis.filter((api) => selectedApiIds.includes(api.id)).length}/
                          {featureApis.length}
                        </Badge>
                      )}
                    </button>
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {/* Right panel - APIs (70%) */}
        <div className="flex min-w-0 flex-1 flex-col overflow-hidden">
          {/* Search and Filters */}
          {/* Search and Filters */}
          <div className="shrink-0 space-y-3 border-b p-3">
            <div className="flex gap-2">
              <div className="relative flex-1">
                <Search className="absolute top-2.5 left-2 h-4 w-4 text-gray-400" />
                <Input
                  placeholder={tWizard('searchApi')}
                  value={searchApi}
                  onChange={(e) => setSearchApi(e.target.value)}
                  className="pl-8 text-sm"
                />
              </div>
              <Select value={methodFilter} onValueChange={setMethodFilter}>
                <SelectTrigger className="h-9 w-[150px] text-sm">
                  <SelectValue placeholder={tWizard('filterByMethod')} />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="*">{tWizard('all')}</SelectItem>
                  {Object.values(HTTP_METHODS).map((method) => (
                    <SelectItem key={method} value={method}>
                      {method}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Checkbox
                  id="toggle-all-visible"
                  checked={
                    Object.keys(filteredGroupedApis).length > 0 &&
                    Object.values(filteredGroupedApis).every(({ apis }) =>
                      apis.every((api: ApiMst) => selectedApiIds.includes(api.id)),
                    )
                  }
                  onCheckedChange={(checked) => {
                    const allVisibleApis = Object.values(filteredGroupedApis).flatMap(
                      (g) => g.apis,
                    );
                    const allVisibleApiIds = allVisibleApis.map((api) => api.id);

                    if (checked) {
                      // Select all visible
                      const newSelected = Array.from(
                        new Set([...selectedApiIds, ...allVisibleApiIds]),
                      );
                      onSelectedApisChange(newSelected);
                    } else {
                      // Deselect all visible
                      const newSelected = selectedApiIds.filter(
                        (id) => !allVisibleApiIds.includes(id),
                      );
                      onSelectedApisChange(newSelected);
                    }
                  }}
                />
                <label htmlFor="toggle-all-visible" className="cursor-pointer text-sm font-medium">
                  {tWizard('all')}
                </label>
              </div>

              <div className="flex h-9 min-w-[80px] items-center justify-center rounded-md border px-3 text-sm">
                <span className="font-semibold">{selectedCount}</span>{' '}
                <span className="mx-1 text-muted-foreground">/</span>{' '}
                <span className="font-semibold">{totalApis}</span>
              </div>
            </div>
          </div>

          {/* APIs grouped by feature */}
          <div className="min-h-0 flex-1 space-y-4 overflow-y-auto p-3">
            {isLoading ? (
              <div className="text-center text-sm text-gray-500">{tCommon('loading')}...</div>
            ) : Object.keys(filteredGroupedApis).length === 0 ? (
              <div className="py-8 text-center text-sm text-gray-500">{tWizard('noApis')}</div>
            ) : (
              Object.entries(filteredGroupedApis).map(([featureId, { feature, apis }]) => {
                const allChecked = apis.every((api: ApiMst) => selectedApiIds.includes(api.id));

                return (
                  <div
                    key={feature.id}
                    id={`feature-${feature.id}`}
                    className="space-y-2 border-b pb-4 last:border-b-0"
                  >
                    {/* Feature header with checkbox */}
                    <div className="mb-3 flex items-center gap-2">
                      <Checkbox
                        id={`feature-${feature.id}`}
                        checked={allChecked}
                        onCheckedChange={() => handleToggleFeature(parseInt(featureId))}
                      />
                      <label
                        htmlFor={`feature-${feature.id}`}
                        className="cursor-pointer text-sm font-semibold text-gray-700"
                      >
                        {feature.name}
                      </label>
                      <Badge variant="secondary" className="text-xs">
                        {apis.length}
                      </Badge>
                    </div>

                    {/* APIs under this feature */}
                    <div className="space-y-2 pl-6">
                      {apis.map((api: ApiMst) => {
                        const methodName = API_TYPE_TO_METHOD[api.type] || HTTP_METHODS.GET;
                        const methodInfo = HTTP_METHOD_LABELS[methodName] || {
                          label: 'UNKNOWN',
                          color: 'bg-gray-100 text-gray-800',
                        };
                        const isChecked = selectedApiIds.includes(api.id);

                        return (
                          <div
                            key={api.id}
                            className="flex items-start gap-3 rounded-md p-2 transition-colors hover:bg-gray-50"
                          >
                            <Checkbox
                              id={`api-${api.id}`}
                              checked={isChecked}
                              onCheckedChange={() => handleToggleApi(api.id)}
                              className="mt-1"
                            />
                            <label
                              htmlFor={`api-${api.id}`}
                              className="flex flex-1 cursor-pointer items-start gap-2"
                            >
                              <div className="min-w-0 flex-1">
                                <div className="mb-1 flex items-center gap-2">
                                  <Badge className={`text-xs ${methodInfo.color}`}>
                                    {methodInfo.label}
                                  </Badge>
                                  <span className="text-sm font-semibold text-gray-900">
                                    {api.name}
                                  </span>
                                </div>
                                <p className="text-xs break-words text-gray-500">{api.path}</p>
                              </div>
                            </label>
                          </div>
                        );
                      })}
                    </div>
                  </div>
                );
              })
            )}
          </div>
        </div>
      </div>

      {/* Info box */}
    </div>
  );
}
