'use client';

import { useState, useMemo, useCallback } from 'react';
import { useTranslations } from 'next-intl';
import { useApiData } from '@/shared/hooks/use-api-data';
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
import { HTTP_METHODS } from '@/features/roles/role-wizard.constant';
import type { Step2PermissionSetupProps } from '@/features/roles/role-wizard.types';
import {
  ALL_METHODS,
  filterGroupedApis,
  groupApisByFeature,
  toggleIds,
} from '@/features/roles/permission-groups';
import { FeatureApiGroup } from './feature-api-group';

export function Step2PermissionSetup({
  selectedApiIds,
  onSelectedApisChange,
}: Step2PermissionSetupProps) {
  const tCommon = useTranslations('common');
  const tWizard = useTranslations('roleWizard');

  const [searchApi, setSearchApi] = useState('');
  const [searchFeature, setSearchFeature] = useState('');
  const [methodFilter, setMethodFilter] = useState<string>(ALL_METHODS);

  // Fetch APIs
  const { data: allApis, loading: apisLoading } = useApiData<ApiMst>(API_ENDPOINTS.MASTER.API, {
    per_page: 1000,
  });

  // Fetch Features
  const { data: allFeatures, loading: featuresLoading } = useApiData<FeatureMst>(
    API_ENDPOINTS.MASTER.FEATURE,
    { per_page: 1000 },
  );

  const groupedApis = useMemo(
    () => groupApisByFeature(allFeatures, allApis),
    [allApis, allFeatures],
  );

  const filteredGroupedApis = useMemo(
    () => filterGroupedApis(groupedApis, { searchApi, searchFeature, method: methodFilter }),
    [groupedApis, searchApi, searchFeature, methodFilter],
  );

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
      onSelectedApisChange(toggleIds(selectedApiIds, featureApiIds, !allChecked));
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
                    const allVisibleApiIds = Object.values(filteredGroupedApis).flatMap((g) =>
                      g.apis.map((api: ApiMst) => api.id),
                    );
                    onSelectedApisChange(toggleIds(selectedApiIds, allVisibleApiIds, !!checked));
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
              Object.entries(filteredGroupedApis).map(([featureId, { feature, apis }]) => (
                <FeatureApiGroup
                  key={feature.id}
                  feature={feature}
                  apis={apis}
                  selectedApiIds={selectedApiIds}
                  onToggleFeature={() => handleToggleFeature(parseInt(featureId))}
                  onToggleApi={handleToggleApi}
                />
              ))
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
