/** `data` of the JSON 200 response of a generated operation (the envelope's payload). */
export type OkData<Operation> = Operation extends {
  responses: { 200: { content: { 'application/json': { data: infer Data } } } };
}
  ? Data
  : never;
