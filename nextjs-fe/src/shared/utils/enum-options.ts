/** Select options from an enum label map, e.g. `enumOptions(IsActiveLabels)`. */
export const enumOptions = (labels: Record<string | number, string>) =>
  Object.entries(labels).map(([value, label]) => ({ value, label }));
