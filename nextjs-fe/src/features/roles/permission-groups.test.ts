import { describe, expect, it } from 'vitest';
import type { ApiMst, FeatureMst } from '@/shared/types/api';
import { ALL_METHODS, filterGroupedApis, groupApisByFeature, toggleIds } from './permission-groups';

const feature = (id: number, name: string) => ({ id, name }) as FeatureMst;
const api = (id: number, feature_mst_id: number, name: string, path: string, type = 1) =>
  ({ id, feature_mst_id, name, path, type }) as ApiMst;

const features = [feature(1, 'Users'), feature(2, 'Banners')];
const apis = [
  api(10, 1, 'List users', '/users/list', 1),
  api(11, 1, 'Create user', '/users/store', 2),
  api(20, 2, 'List banners', '/banners/list', 1),
  api(99, 3, 'Orphan', '/orphan', 1),
];
const grouped = groupApisByFeature(features, apis);
const noFilter = { searchApi: '', searchFeature: '', method: ALL_METHODS };
const idsOf = (g: ReturnType<typeof filterGroupedApis>) =>
  Object.values(g).map(({ feature, apis }) => [feature.id, apis.map((a: ApiMst) => a.id)]);

describe('permission groups', () => {
  it('groups APIs under their feature and drops unknown features', () => {
    expect(idsOf(grouped)).toEqual([
      [1, [10, 11]],
      [2, [20]],
    ]);
  });

  it('filters by feature name and API name or path', () => {
    expect(idsOf(filterGroupedApis(grouped, { ...noFilter, searchFeature: 'ban' }))).toEqual([
      [2, [20]],
    ]);
    expect(idsOf(filterGroupedApis(grouped, { ...noFilter, searchApi: 'STORE' }))).toEqual([
      [1, [11]],
    ]);
  });

  it('filters by HTTP method name', () => {
    expect(idsOf(filterGroupedApis(grouped, { ...noFilter, method: 'POST' }))).toEqual([[1, [11]]]);
    expect(idsOf(filterGroupedApis(grouped, { ...noFilter, method: 'GET' }))).toEqual([
      [1, [10]],
      [2, [20]],
    ]);
  });

  it('keeps empty features only while no API filter is active', () => {
    const empty = groupApisByFeature([feature(5, 'Empty')], []);
    expect(idsOf(filterGroupedApis(empty, noFilter))).toEqual([[5, []]]);
    expect(idsOf(filterGroupedApis(empty, { ...noFilter, searchApi: 'x' }))).toEqual([]);
  });

  it('toggleIds adds without duplicates and removes', () => {
    expect(toggleIds([1, 2], [2, 3], true)).toEqual([1, 2, 3]);
    expect(toggleIds([1, 2, 3], [2, 3], false)).toEqual([1]);
  });
});
