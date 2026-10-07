'use client';

import { useMemo, useState } from 'react';
import { useTranslations } from 'next-intl';
import { useApiData } from '@/shared/hooks/use-api-data';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { API_ENDPOINTS } from '@/shared/api';
import type { ApiMst, FeatureMst } from '@/shared/types/api';
import { IsActiveLabels, IsActive } from '@/shared/enums';
import { AlertCircle, Check } from 'lucide-react';
import { HTTP_METHOD_LABELS, API_TYPE_TO_METHOD } from '@/features/roles/role-wizard.constant';
import type { Step3ReviewConfirmProps } from '@/features/roles/role-wizard.types';

export function Step3ReviewConfirm({ roleData, selectedApiIds, isEdit }: Step3ReviewConfirmProps) {
  const tCommon = useTranslations('common');
  const tLabels = useTranslations('forms.labels');
  const tWizard = useTranslations('roleWizard');

  const { data: allApis } = useApiData<ApiMst>(API_ENDPOINTS.MASTER.API, { per_page: 1000 });

  const { data: allFeatures } = useApiData<FeatureMst>(API_ENDPOINTS.MASTER.FEATURE, {
    per_page: 1000,
  });

  // State for collapsible features
  const [expandedFeatures, setExpandedFeatures] = useState<number[]>([]);

  // Build selected APIs list and group by feature
  const apisByFeature = useMemo(() => {
    const selected = allApis.filter((api) => selectedApiIds.includes(api.id));

    const grouped = new Map<number, { feature: FeatureMst; apis: ApiMst[] }>();

    selected.forEach((api) => {
      const feature = allFeatures.find((f) => f.id === api.feature_mst_id);
      if (feature) {
        if (!grouped.has(feature.id)) {
          grouped.set(feature.id, { feature, apis: [] });
        }
        grouped.get(feature.id)!.apis.push(api);
      }
    });

    return grouped;
  }, [allApis, allFeatures, selectedApiIds]);

  return (
    <div className="space-y-6">
      {/* Role Information Section */}
      <div className="space-y-4">
        <div className="flex items-center gap-2">
          <h3 className="text-lg font-semibold">{tWizard('roleInformation')}</h3>
          <Check className="h-5 w-5 text-green-500" />
        </div>

        <div className="space-y-3 rounded-lg border bg-gray-50 p-4">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <p className="text-xs font-semibold text-gray-500 uppercase">{tLabels('name')}</p>
              <p className="mt-1 text-sm font-medium text-gray-900">{roleData.name}</p>
            </div>

            <div>
              <p className="text-xs font-semibold text-gray-500 uppercase">
                {tLabels('permission')}
              </p>
              <p className="mt-1 text-sm font-medium text-gray-900">{roleData.permission}</p>
            </div>

            <div>
              <p className="text-xs font-semibold text-gray-500 uppercase">{tLabels('status')}</p>
              <Badge variant={roleData.is_active ? 'default' : 'secondary'} className="mt-1">
                {roleData.is_active
                  ? IsActiveLabels[IsActive.TRUE]
                  : IsActiveLabels[IsActive.FALSE]}
              </Badge>
            </div>

            <div>
              <p className="text-xs font-semibold text-gray-500 uppercase">
                {tWizard('operationType')}
              </p>
              <p className="mt-1 text-sm font-medium text-gray-900">
                {isEdit ? tCommon('update') : tCommon('create')}
              </p>
            </div>
          </div>
        </div>
      </div>

      <Separator />

      {/* Permission/APIs Section */}
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <h3 className="text-lg font-semibold">{tLabels('permission')}</h3>
            <Badge variant="outline">{selectedApiIds.length}</Badge>
          </div>
        </div>

        {selectedApiIds.length === 0 ? (
          <div className="flex gap-3 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
            <AlertCircle className="mt-0.5 h-5 w-5 flex-shrink-0 text-yellow-600" />
            <p className="text-sm text-yellow-800">{tWizard('noPermissionsSelected')}</p>
          </div>
        ) : (
          <div className="space-y-4">
            {Array.from(apisByFeature.values()).map((group) => {
              const isExpanded = expandedFeatures.includes(group.feature.id);

              const toggleFeature = () => {
                setExpandedFeatures((prev) =>
                  isExpanded
                    ? prev.filter((id) => id !== group.feature.id)
                    : [...prev, group.feature.id],
                );
              };

              return (
                <div key={group.feature.id} className="space-y-2">
                  <div
                    className="flex cursor-pointer items-center justify-between rounded-lg border border-blue-200 bg-blue-50 p-3 transition-colors hover:bg-blue-100"
                    onClick={toggleFeature}
                  >
                    <div>
                      <h4 className="text-sm font-semibold text-blue-900">{group.feature.name}</h4>
                      <p className="mt-1 text-xs text-blue-700">
                        {group.apis.length} {tWizard('apisInThisFeature')}
                      </p>
                    </div>
                    <div className="flex items-center gap-2">
                      <Badge variant="secondary" className="bg-white/50 text-blue-900">
                        {isExpanded ? tCommon('hide') : tCommon('show')}
                      </Badge>
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        className={`h-5 w-5 text-blue-800 transition-transform ${isExpanded ? 'rotate-180' : ''}`}
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M19 9l-7 7-7-7"
                        />
                      </svg>
                    </div>
                  </div>

                  {isExpanded && (
                    <div className="animate-in space-y-2 pl-2 duration-200 slide-in-from-top-2">
                      {group.apis.map((api) => {
                        const methodName = API_TYPE_TO_METHOD[api.type] || 'GET';
                        const methodInfo = HTTP_METHOD_LABELS[methodName] || {
                          label: 'UNKNOWN',
                          color: 'bg-gray-100 text-gray-800',
                        };

                        return (
                          <div
                            key={api.id}
                            className="flex items-start gap-3 rounded-lg border bg-gray-50 p-3"
                          >
                            <Check className="mt-1 h-4 w-4 flex-shrink-0 text-green-500" />
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
                          </div>
                        );
                      })}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        )}
      </div>

      <Separator />

      {/* Final Confirmation Info */}
      <div className="rounded-lg border border-green-200 bg-green-50 p-4">
        <p className="text-sm font-medium text-green-800">{tWizard('confirmationInfo')}</p>
      </div>
    </div>
  );
}
