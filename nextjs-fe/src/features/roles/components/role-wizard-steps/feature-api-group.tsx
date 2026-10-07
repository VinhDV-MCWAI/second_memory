'use client';

import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import type { ApiMst, FeatureMst } from '@/shared/types/api';
import {
  HTTP_METHODS,
  HTTP_METHOD_LABELS,
  API_TYPE_TO_METHOD,
} from '@/features/roles/role-wizard.constant';

const UNKNOWN_METHOD = { label: 'UNKNOWN', color: 'bg-gray-100 text-gray-800' };

interface FeatureApiGroupProps {
  feature: FeatureMst;
  apis: ApiMst[];
  selectedApiIds: number[];
  onToggleFeature: () => void;
  onToggleApi: (apiId: number) => void;
}

/** One feature with its APIs as checkboxes (right panel of the permission step). */
export function FeatureApiGroup({
  feature,
  apis,
  selectedApiIds,
  onToggleFeature,
  onToggleApi,
}: FeatureApiGroupProps) {
  const allChecked = apis.every((api) => selectedApiIds.includes(api.id));

  return (
    <div id={`feature-${feature.id}`} className="space-y-2 border-b pb-4 last:border-b-0">
      {/* Feature header with checkbox */}
      <div className="mb-3 flex items-center gap-2">
        <Checkbox
          id={`feature-${feature.id}`}
          checked={allChecked}
          onCheckedChange={onToggleFeature}
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
        {apis.map((api) => {
          const methodName = API_TYPE_TO_METHOD[api.type] || HTTP_METHODS.GET;
          const methodInfo = HTTP_METHOD_LABELS[methodName] || UNKNOWN_METHOD;

          return (
            <div
              key={api.id}
              className="flex items-start gap-3 rounded-md p-2 transition-colors hover:bg-gray-50"
            >
              <Checkbox
                id={`api-${api.id}`}
                checked={selectedApiIds.includes(api.id)}
                onCheckedChange={() => onToggleApi(api.id)}
                className="mt-1"
              />
              <label
                htmlFor={`api-${api.id}`}
                className="flex flex-1 cursor-pointer items-start gap-2"
              >
                <div className="min-w-0 flex-1">
                  <div className="mb-1 flex items-center gap-2">
                    <Badge className={`text-xs ${methodInfo.color}`}>{methodInfo.label}</Badge>
                    <span className="text-sm font-semibold text-gray-900">{api.name}</span>
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
}
