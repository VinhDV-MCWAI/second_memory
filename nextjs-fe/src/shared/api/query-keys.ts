/**
 * TanStack Query keys. Every key starts with the resource endpoint, so invalidating
 * `queryKeys.resource(endpoint)` refreshes all cached lists of that resource.
 */
export const queryKeys = {
  resource: (endpoint: string) => [endpoint] as const,
  list: (endpoint: string, params: Record<string, unknown>) => [endpoint, 'list', params] as const,
};
